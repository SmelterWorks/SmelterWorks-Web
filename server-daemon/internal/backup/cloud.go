package backup

import (
	"context"
	"fmt"
	"net/http"
	"os"
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
