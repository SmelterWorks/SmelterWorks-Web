package files

import (
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
)

type Service struct {
	roots []string
}

func New(roots ...string) *Service {
	return &Service{roots: roots}
}

func (s *Service) List(rel string) ([]os.DirEntry, error) {
	abs, err := s.resolve(rel)
	if err != nil {
		return nil, err
	}
	return os.ReadDir(abs)
}

func (s *Service) Read(rel string) ([]byte, error) {
	abs, err := s.resolve(rel)
	if err != nil {
		return nil, err
	}
	return os.ReadFile(abs)
}

func (s *Service) Write(rel string, r io.Reader) error {
	abs, err := s.resolve(rel)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(abs), 0o755); err != nil {
		return err
	}
	f, err := os.Create(abs)
	if err != nil {
		return err
	}
	defer f.Close()
	_, err = io.Copy(f, r)
	return err
}

func (s *Service) UploadHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		rel := r.URL.Query().Get("path")
		if err := s.Write(rel, r.Body); err != nil {
			http.Error(w, err.Error(), http.StatusBadRequest)
			return
		}
		w.WriteHeader(http.StatusCreated)
	}
}

func (s *Service) resolve(rel string) (string, error) {
	clean := filepath.Clean("/" + rel)
	if strings.Contains(clean, "..") {
		return "", fmt.Errorf("path traversal blocked")
	}
	for _, root := range s.roots {
		candidate := filepath.Join(root, strings.TrimPrefix(clean, "/"))
		absRoot, err := filepath.Abs(root)
		if err != nil {
			continue
		}
		absCandidate, err := filepath.Abs(candidate)
		if err != nil {
			continue
		}
		if strings.HasPrefix(absCandidate, absRoot+string(os.PathSeparator)) || absCandidate == absRoot {
			return absCandidate, nil
		}
	}
	return "", fmt.Errorf("path not allowed")
}
