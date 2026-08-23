package backup

import (
	"archive/tar"
	"compress/gzip"
	"context"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"time"

	"github.com/smelterworks/server-daemon/internal/config"
)

type Service struct {
	cfg config.Config
}

func New(cfg config.Config) *Service {
	return &Service{cfg: cfg}
}

func (s *Service) CreateLocal(ctx context.Context) (string, error) {
	_ = ctx
	if err := os.MkdirAll(s.cfg.BackupPath, 0o755); err != nil {
		return "", err
	}
	name := fmt.Sprintf("vs-backup-%s.tar.gz", time.Now().UTC().Format("20060102T150405Z"))
	dest := filepath.Join(s.cfg.BackupPath, name)
	tmp := dest + ".partial"
	f, err := os.Create(tmp)
	if err != nil {
		return "", err
	}
	gz := gzip.NewWriter(f)
	tw := tar.NewWriter(gz)
	includes := []string{"Saves", "Playerdata", "Mods", "serverconfig.json"}
	for _, part := range includes {
		src := filepath.Join(s.cfg.DataPath, part)
		if _, err := os.Stat(src); err != nil {
			continue
		}
		if err := addPath(tw, src, part); err != nil {
			_ = tw.Close()
			_ = gz.Close()
			_ = f.Close()
			return "", err
		}
	}
	if err := tw.Close(); err != nil {
		return "", err
	}
	if err := gz.Close(); err != nil {
		return "", err
	}
	if err := f.Close(); err != nil {
		return "", err
	}
	if err := os.Rename(tmp, dest); err != nil {
		return "", err
	}
	return dest, nil
}

func addPath(tw *tar.Writer, src, name string) error {
	return filepath.Walk(src, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		if info.IsDir() {
			return nil
		}
		rel, err := filepath.Rel(src, path)
		if err != nil {
			return err
		}
		hdr, err := tar.FileInfoHeader(info, "")
		if err != nil {
			return err
		}
		hdr.Name = filepath.ToSlash(filepath.Join(name, rel))
		if err := tw.WriteHeader(hdr); err != nil {
			return err
		}
		f, err := os.Open(path)
		if err != nil {
			return err
		}
		defer f.Close()
		_, err = io.Copy(tw, f)
		return err
	})
}
