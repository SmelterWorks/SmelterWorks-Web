package localapi

import (
	"encoding/json"
	"fmt"
	"net"
	"net/http"
	"os"
	"strings"

	"github.com/smelterworks/server-daemon/internal/backup"
	"github.com/smelterworks/server-daemon/internal/config"
	"github.com/smelterworks/server-daemon/internal/docker"
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
