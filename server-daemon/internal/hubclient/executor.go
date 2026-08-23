package hubclient

import (
	"context"
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/smelterworks/server-daemon/internal/backup"
	"github.com/smelterworks/server-daemon/internal/config"
	"github.com/smelterworks/server-daemon/internal/docker"
	"github.com/smelterworks/server-daemon/internal/migrate"
)

type Executor struct {
	cfg    config.Config
	docker *docker.Client
	backup *backup.Service
	cloud  *backup.CloudUploader
	local  *http.Client
}

func NewExecutor(cfg config.Config, dockerClient *docker.Client, backupSvc *backup.Service) *Executor {
	return &Executor{
		cfg:    cfg,
		docker: dockerClient,
		backup: backupSvc,
		cloud:  backup.NewCloudUploader(cfg),
		local: &http.Client{
			Timeout: 15 * time.Minute,
			Transport: &http.Transport{
				DialContext: func(ctx context.Context, _, _ string) (net.Conn, error) {
					var d net.Dialer
					return d.DialContext(ctx, "unix", cfg.LocalSocket)
				},
			},
		},
	}
}

func (e *Executor) Run(ctx context.Context, cmd Command) (map[string]interface{}, error) {
	switch cmd.Type {
	case "power.start":
		return e.runPower(ctx, "/start")
	case "power.stop":
		return e.runPower(ctx, "/stop")
	case "power.restart":
		return e.runPower(ctx, "/restart")
	case "backup.create":
		return e.runBackup(ctx)
	case "migrate.export":
		return e.runMigrateExport(ctx, cmd.Payload)
	case "migrate.import":
		return e.runMigrateImport(ctx, cmd.Payload)
	case "mod.install":
		return e.runModInstall(ctx, cmd.Payload)
	default:
		return nil, fmt.Errorf("unknown command type: %s", cmd.Type)
	}
}

func (e *Executor) runPower(ctx context.Context, path string) (map[string]interface{}, error) {
	if err := e.localPost(ctx, path); err != nil {
		return nil, err
	}
	return map[string]interface{}{"ok": true}, nil
}

func (e *Executor) runBackup(ctx context.Context) (map[string]interface{}, error) {
	path, err := e.backup.CreateLocal(ctx)
	if err != nil {
		return nil, err
	}
	info, err := os.Stat(path)
	if err != nil {
		return nil, err
	}
	checksum, err := hashFile(path)
	if err != nil {
		return nil, err
	}
	return map[string]interface{}{
		"path":     path,
		"bytes":    info.Size(),
		"checksum": checksum,
	}, nil
}

func (e *Executor) runMigrateExport(ctx context.Context, payload map[string]interface{}) (map[string]interface{}, error) {
	jobUUID, _ := payload["job_uuid"].(string)
	stagingKey, _ := payload["staging_key"].(string)
	serverUUID, _ := payload["server_uuid"].(string)
	if serverUUID == "" {
		serverUUID = jobUUID
	}

	if err := e.docker.Stop(ctx); err != nil {
		return nil, err
	}

	dest := filepath.Join(e.cfg.BackupPath, fmt.Sprintf("migrate-%s.tar.gz", jobUUID))
	if err := os.MkdirAll(e.cfg.BackupPath, 0o755); err != nil {
		return nil, err
	}

	path, err := migrate.ExportPackage(e.cfg.DataPath, dest, serverUUID)
	if err != nil {
		return nil, err
	}

	info, err := os.Stat(path)
	if err != nil {
		return nil, err
	}

	packageHash, err := hashFile(path)
	if err != nil {
		return nil, err
	}

	result := map[string]interface{}{
		"path":          path,
		"bytes":         info.Size(),
		"package_hash":  packageHash,
		"staging_key":   stagingKey,
	}

	if stagingKey != "" && e.cfg.S3Endpoint != "" && e.cfg.S3Bucket != "" {
		if err := e.cloud.Upload(ctx, path, stagingKey); err != nil {
			return nil, err
		}
	}

	return result, nil
}

func (e *Executor) runMigrateImport(ctx context.Context, payload map[string]interface{}) (map[string]interface{}, error) {
	stagingKey, _ := payload["staging_key"].(string)
	packagePath := filepath.Join(e.cfg.BackupPath, "import-"+strings.ReplaceAll(stagingKey, "/", "-"))

	if stagingKey != "" && e.cfg.S3Endpoint != "" && e.cfg.S3Bucket != "" {
		if err := e.cloud.Download(ctx, stagingKey, packagePath); err != nil {
			return nil, err
		}
	} else if path, ok := payload["package_path"].(string); ok && path != "" {
		packagePath = path
	} else {
		return nil, fmt.Errorf("no migration package available")
	}

	if err := e.docker.Stop(ctx); err != nil {
		return nil, err
	}

	if err := migrate.ImportPackage(packagePath, e.cfg.DataPath); err != nil {
		return nil, err
	}

	if err := e.docker.Start(ctx, e.cfg.DataPath, e.cfg.ModsPath, e.cfg.BackupPath); err != nil {
		return nil, err
	}

	return map[string]interface{}{"ok": true}, nil
}

func (e *Executor) runModInstall(ctx context.Context, payload map[string]interface{}) (map[string]interface{}, error) {
	modID, _ := payload["modid"].(string)
	downloadURL, _ := payload["download_url"].(string)
	if modID == "" {
		return nil, fmt.Errorf("missing modid")
	}
	if downloadURL == "" {
		return nil, fmt.Errorf("missing download_url")
	}

	if err := os.MkdirAll(e.cfg.ModsPath, 0o755); err != nil {
		return nil, err
	}

	dest := filepath.Join(e.cfg.ModsPath, modID+".zip")
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, downloadURL, nil)
	if err != nil {
		return nil, err
	}
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 300 {
		return nil, fmt.Errorf("mod download failed: %s", resp.Status)
	}
	out, err := os.Create(dest)
	if err != nil {
		return nil, err
	}
	if _, err := io.Copy(out, resp.Body); err != nil {
		_ = out.Close()
		return nil, err
	}
	if err := out.Close(); err != nil {
		return nil, err
	}

	return map[string]interface{}{"path": dest}, nil
}

func (e *Executor) localPost(ctx context.Context, path string) error {
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, "http://unix"+path, nil)
	if err != nil {
		return err
	}
	if e.cfg.LocalToken != "" {
		req.Header.Set("X-Local-Token", e.cfg.LocalToken)
	}
	resp, err := e.local.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 300 {
		raw, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("%s", string(raw))
	}
	return nil
}

func hashFile(path string) (string, error) {
	f, err := os.Open(path)
	if err != nil {
		return "", err
	}
	defer f.Close()
	h := sha256.New()
	if _, err := io.Copy(h, f); err != nil {
		return "", err
	}
	return hex.EncodeToString(h.Sum(nil)), nil
}
