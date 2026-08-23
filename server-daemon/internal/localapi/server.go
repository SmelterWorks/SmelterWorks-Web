package localapi

import (
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"github.com/smelterworks/server-daemon/internal/backup"
	"github.com/smelterworks/server-daemon/internal/config"
	"github.com/smelterworks/server-daemon/internal/docker"
	"github.com/smelterworks/server-daemon/internal/migrate"
)

type Server struct {
	cfg    config.Config
	docker *docker.Client
	backup *backup.Service
}

func New(cfg config.Config, dockerClient *docker.Client, backupSvc *backup.Service) *Server {
	return &Server{cfg: cfg, docker: dockerClient, backup: backupSvc}
}

func (s *Server) Serve() error {
	if err := os.MkdirAll("/run", 0o755); err != nil {
		return err
	}
	_ = os.Remove(s.cfg.LocalSocket)
	ln, err := net.Listen("unix", s.cfg.LocalSocket)
	if err != nil {
		return err
	}
	_ = os.Chmod(s.cfg.LocalSocket, 0o600)
	mux := http.NewServeMux()
	mux.HandleFunc("/status", s.handleStatus)
	mux.HandleFunc("/start", s.handleStart)
	mux.HandleFunc("/stop", s.handleStop)
	mux.HandleFunc("/restart", s.handleRestart)
	mux.HandleFunc("/backup", s.handleBackup)
	mux.HandleFunc("/migrate/export", s.handleMigrateExport)
	mux.HandleFunc("/migrate/import", s.handleMigrateImport)
	return http.Serve(ln, s.auth(mux))
}

func (s *Server) auth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if s.cfg.LocalToken != "" && r.Header.Get("X-Local-Token") != s.cfg.LocalToken {
			http.Error(w, "unauthorized", http.StatusUnauthorized)
			return
		}
		next.ServeHTTP(w, r)
	})
}

func (s *Server) handleStatus(w http.ResponseWriter, r *http.Request) {
	status, _ := s.docker.Status(r.Context())
	writeJSON(w, map[string]string{"status": status})
}

func (s *Server) handleStart(w http.ResponseWriter, r *http.Request) {
	err := s.docker.Start(r.Context(), s.cfg.DataPath, s.cfg.ModsPath, s.cfg.BackupPath)
	writeErr(w, err)
}

func (s *Server) handleStop(w http.ResponseWriter, r *http.Request) {
	writeErr(w, s.docker.Stop(r.Context()))
}

func (s *Server) handleRestart(w http.ResponseWriter, r *http.Request) {
	writeErr(w, s.docker.Restart(r.Context()))
}

func (s *Server) handleBackup(w http.ResponseWriter, r *http.Request) {
	path, err := s.backup.CreateLocal(r.Context())
	if err != nil {
		writeErr(w, err)
		return
	}
	writeJSON(w, map[string]string{"path": path})
}

func (s *Server) handleMigrateExport(w http.ResponseWriter, r *http.Request) {
	var body struct {
		ServerUUID string `json:"server_uuid"`
		JobUUID    string `json:"job_uuid"`
	}
	if err := decodeJSON(r, &body); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	serverID := body.ServerUUID
	if serverID == "" {
		serverID = body.JobUUID
	}
	if serverID == "" {
		serverID = "local"
	}

	if err := s.docker.Stop(r.Context()); err != nil {
		writeErr(w, err)
		return
	}

	dest := filepath.Join(s.cfg.BackupPath, fmt.Sprintf("migrate-%s.tar.gz", serverID))
	if err := os.MkdirAll(s.cfg.BackupPath, 0o755); err != nil {
		writeErr(w, err)
		return
	}

	path, err := migrate.ExportPackage(s.cfg.DataPath, dest, serverID)
	if err != nil {
		writeErr(w, err)
		return
	}
	writeJSON(w, map[string]string{"path": path})
}

func (s *Server) handleMigrateImport(w http.ResponseWriter, r *http.Request) {
	var body struct {
		PackagePath string `json:"package_path"`
	}
	if err := decodeJSON(r, &body); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	if body.PackagePath == "" {
		http.Error(w, "package_path required", http.StatusBadRequest)
		return
	}

	if err := s.docker.Stop(r.Context()); err != nil {
		writeErr(w, err)
		return
	}
	if err := migrate.ImportPackage(body.PackagePath, s.cfg.DataPath); err != nil {
		writeErr(w, err)
		return
	}
	if err := s.docker.Start(r.Context(), s.cfg.DataPath, s.cfg.ModsPath, s.cfg.BackupPath); err != nil {
		writeErr(w, err)
		return
	}
	writeJSON(w, map[string]string{"ok": "true"})
}

func decodeJSON(r *http.Request, v any) error {
	if r.Body == nil {
		return fmt.Errorf("missing body")
	}
	defer r.Body.Close()
	raw, err := io.ReadAll(r.Body)
	if err != nil {
		return err
	}
	if len(strings.TrimSpace(string(raw))) == 0 {
		return nil
	}
	return json.Unmarshal(raw, v)
}

func writeJSON(w http.ResponseWriter, v any) {
	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(v)
}

func writeErr(w http.ResponseWriter, err error) {
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	writeJSON(w, map[string]string{"ok": "true"})
}

func ParseCommand(args []string) (string, error) {
	if len(args) < 1 {
		return "", fmt.Errorf("missing command")
	}
	return strings.ToLower(args[0]), nil
}
