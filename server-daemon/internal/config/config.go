package config

import (
	"os"
	"path/filepath"
	"strconv"
)

type Config struct {
	HubURL        string
	Token         string
	HubPublicKey  string
	InstallPath   string
	KeyPath       string
	DataPath      string
	ModsPath      string
	BackupPath    string
	DockerImage   string
	ContainerName string
	LocalSocket   string
	LocalToken    string
	SFTPPort      int
	S3Endpoint    string
	S3Bucket      string
	S3Region      string
	S3AccessKey   string
	S3SecretKey   string
	S3PathStyle   bool
}

func Load() Config {
	installPath := env("SMELTER_INSTALL_PATH", "/var/lib/smelterd")
	keyPath := env("SMELTER_KEY_PATH", "")
	if keyPath == "" {
		keyPath = filepath.Join(installPath, "key")
	}

	return Config{
		HubURL:        env("SMELTER_HUB_URL", "http://127.0.0.1:8000"),
		Token:         env("SMELTER_TOKEN", ""),
		HubPublicKey:  env("SMELTER_HUB_PUBLIC_KEY", ""),
		InstallPath:   installPath,
		KeyPath:       keyPath,
		DataPath:      env("SMELTER_DATA_PATH", "/data"),
		ModsPath:      env("SMELTER_MODS_PATH", "/mods"),
		BackupPath:    env("SMELTER_BACKUP_PATH", "/backups"),
		DockerImage:   env("SMELTER_DOCKER_IMAGE", "ghcr.io/smelterworks/vs-dockerized-server:latest"),
		ContainerName: env("SMELTER_CONTAINER_NAME", "vintagestory"),
		LocalSocket:   env("SMELTER_LOCAL_SOCKET", "/run/smelterd.sock"),
		LocalToken:    env("SMELTER_LOCAL_TOKEN", ""),
		SFTPPort:      envInt("SMELTER_SFTP_PORT", 2222),
		S3Endpoint:    env("AWS_ENDPOINT", ""),
		S3Bucket:      env("AWS_BUCKET", ""),
		S3Region:      env("AWS_DEFAULT_REGION", "us-east-1"),
		S3AccessKey:   env("AWS_ACCESS_KEY_ID", ""),
		S3SecretKey:   env("AWS_SECRET_ACCESS_KEY", ""),
		S3PathStyle:   env("AWS_USE_PATH_STYLE_ENDPOINT", "false") == "true",
	}
}

func env(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func envInt(key string, fallback int) int {
	v := os.Getenv(key)
	if v == "" {
		return fallback
	}
	n, err := strconv.Atoi(v)
	if err != nil {
		return fallback
	}
	return n
}
