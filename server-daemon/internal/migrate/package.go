package migrate

import (
	"archive/tar"
	"compress/gzip"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"time"
)

type Manifest struct {
	SchemaVersion int               `json:"schemaVersion"`
	ServerID      string            `json:"serverId"`
	CreatedAt     string            `json:"createdAt"`
	Files         map[string]string `json:"files"`
}

func ExportPackage(dataPath, destPath, serverID string) (string, error) {
	manifest := Manifest{
		SchemaVersion: 1,
		ServerID:      serverID,
		CreatedAt:     time.Now().UTC().Format(time.RFC3339),
		Files:         map[string]string{},
	}
	tmp := destPath + ".partial"
	f, err := os.Create(tmp)
	if err != nil {
		return "", err
	}
	gz := gzip.NewWriter(f)
	tw := tar.NewWriter(gz)
	includes := []string{"Saves", "Playerdata", "Mods", "serverconfig.json"}
	for _, part := range includes {
		src := filepath.Join(dataPath, part)
		if _, err := os.Stat(src); err != nil {
			continue
		}
		if err := addWithHash(tw, src, part, manifest.Files); err != nil {
			return "", err
		}
	}
	manifestRaw, _ := json.Marshal(manifest)
	if err := writeBytes(tw, "manifest.json", manifestRaw); err != nil {
		return "", err
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
	if err := os.Rename(tmp, destPath); err != nil {
		return "", err
	}
	return destPath, nil
}

func ImportPackage(packagePath, dataPath string) error {
	f, err := os.Open(packagePath)
	if err != nil {
		return err
	}
	defer f.Close()
	gz, err := gzip.NewReader(f)
	if err != nil {
		return err
	}
	defer gz.Close()
	tr := tar.NewReader(gz)
	manifest := Manifest{Files: map[string]string{}}
	for {
		hdr, err := tr.Next()
		if err == io.EOF {
			break
		}
		if err != nil {
			return err
		}
		if hdr.Name == "manifest.json" {
			raw, err := io.ReadAll(tr)
			if err != nil {
				return err
			}
			if err := json.Unmarshal(raw, &manifest); err != nil {
				return err
			}
			continue
		}
		dest := filepath.Join(dataPath, hdr.Name)
		if err := os.MkdirAll(filepath.Dir(dest), 0o755); err != nil {
			return err
		}
		out, err := os.OpenFile(dest, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, os.FileMode(hdr.Mode))
		if err != nil {
			return err
		}
		if _, err := io.Copy(out, tr); err != nil {
			_ = out.Close()
			return err
		}
		_ = out.Close()
	}
	for name, expected := range manifest.Files {
		sum, err := hashFile(filepath.Join(dataPath, name))
		if err != nil {
			return err
		}
		if sum != expected {
			return fmt.Errorf("checksum mismatch for %s", name)
		}
	}
	return nil
}

func addWithHash(tw *tar.Writer, src, name string, files map[string]string) error {
	return filepath.Walk(src, func(path string, info os.FileInfo, err error) error {
		if err != nil || info.IsDir() {
			return err
		}
		rel, err := filepath.Rel(src, path)
		if err != nil {
			return err
		}
		entry := filepath.ToSlash(filepath.Join(name, rel))
		sum, err := hashFile(path)
		if err != nil {
			return err
		}
		files[entry] = sum
		hdr, err := tar.FileInfoHeader(info, "")
		if err != nil {
			return err
		}
		hdr.Name = entry
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

func writeBytes(tw *tar.Writer, name string, data []byte) error {
	hdr := &tar.Header{
		Name:    name,
		Mode:    0o644,
		Size:    int64(len(data)),
		ModTime: time.Now(),
	}
	if err := tw.WriteHeader(hdr); err != nil {
		return err
	}
	_, err := tw.Write(data)
	return err
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
