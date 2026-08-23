package backup

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/smelterworks/server-daemon/internal/config"
)

type CloudUploader struct {
	cfg config.Config
}

func NewCloudUploader(cfg config.Config) *CloudUploader {
	return &CloudUploader{cfg: cfg}
}

func (u *CloudUploader) Upload(ctx context.Context, localPath, objectKey string) error {
	if u.cfg.S3Endpoint == "" || u.cfg.S3Bucket == "" {
		return fmt.Errorf("cloud backup not configured")
	}
	f, err := os.Open(localPath)
	if err != nil {
		return err
	}
	defer f.Close()
	endpoint := strings.TrimRight(u.cfg.S3Endpoint, "/")
	url := fmt.Sprintf("%s/%s/%s", endpoint, u.cfg.S3Bucket, objectKey)
	req, err := http.NewRequestWithContext(ctx, http.MethodPut, url, f)
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/gzip")
	if u.cfg.S3AccessKey != "" {
		req.SetBasicAuth(u.cfg.S3AccessKey, u.cfg.S3SecretKey)
	}
	client := &http.Client{Timeout: 10 * time.Minute}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 300 {
		return fmt.Errorf("upload failed: %s", resp.Status)
	}
	return nil
}

func (u *CloudUploader) Download(ctx context.Context, objectKey, localPath string) error {
	if u.cfg.S3Endpoint == "" || u.cfg.S3Bucket == "" {
		return fmt.Errorf("cloud backup not configured")
	}
	endpoint := strings.TrimRight(u.cfg.S3Endpoint, "/")
	url := fmt.Sprintf("%s/%s/%s", endpoint, u.cfg.S3Bucket, objectKey)
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return err
	}
	if u.cfg.S3AccessKey != "" {
		req.SetBasicAuth(u.cfg.S3AccessKey, u.cfg.S3SecretKey)
	}
	client := &http.Client{Timeout: 10 * time.Minute}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 300 {
		return fmt.Errorf("download failed: %s", resp.Status)
	}
	if err := os.MkdirAll(filepath.Dir(localPath), 0o755); err != nil {
		return err
	}
	out, err := os.Create(localPath)
	if err != nil {
		return err
	}
	if _, err := io.Copy(out, resp.Body); err != nil {
		_ = out.Close()
		return err
	}
	return out.Close()
}
