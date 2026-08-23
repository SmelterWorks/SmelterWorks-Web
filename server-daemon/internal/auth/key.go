package auth

import (
	"crypto/rand"
	"os"
	"path/filepath"
)

func EnsureInstallKey(installPath, keyPath string) error {
	if err := os.MkdirAll(installPath, 0o700); err != nil {
		return err
	}

	if _, err := os.Stat(keyPath); err == nil {
		return nil
	}

	key := make([]byte, 32)
	if _, err := rand.Read(key); err != nil {
		return err
	}

	tmp := keyPath + ".tmp"
	if err := os.WriteFile(tmp, key, 0o600); err != nil {
		return err
	}

	return os.Rename(tmp, keyPath)
}

func DefaultInstallPath() string {
	return filepath.Clean("/var/lib/smelterd")
}

func DefaultKeyPath(installPath string) string {
	return filepath.Join(installPath, "key")
}
