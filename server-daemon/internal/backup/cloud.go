package backup

import (
	"context"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"

	"github.com/aws/aws-sdk-go-v2/aws"
	awsconfig "github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/credentials"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	"github.com/smelterworks/server-daemon/internal/config"
)

type CloudUploader struct {
	cfg config.Config
}

func NewCloudUploader(cfg config.Config) *CloudUploader {
	return &CloudUploader{cfg: cfg}
}

func (u *CloudUploader) client(ctx context.Context) (*s3.Client, error) {
	if u.cfg.S3Endpoint == "" || u.cfg.S3Bucket == "" {
		return nil, fmt.Errorf("cloud backup not configured")
	}

	loadOpts := []func(*awsconfig.LoadOptions) error{
		awsconfig.WithRegion(u.cfg.S3Region),
	}

	if u.cfg.S3AccessKey != "" {
		loadOpts = append(loadOpts, awsconfig.WithCredentialsProvider(
			credentials.NewStaticCredentialsProvider(u.cfg.S3AccessKey, u.cfg.S3SecretKey, ""),
		))
	}

	awsCfg, err := awsconfig.LoadDefaultConfig(ctx, loadOpts...)
	if err != nil {
		return nil, err
	}

	client := s3.NewFromConfig(awsCfg, func(o *s3.Options) {
		o.BaseEndpoint = aws.String(strings.TrimRight(u.cfg.S3Endpoint, "/"))
		o.UsePathStyle = u.cfg.S3PathStyle
	})

	return client, nil
}

func (u *CloudUploader) Upload(ctx context.Context, localPath, objectKey string) error {
	client, err := u.client(ctx)
	if err != nil {
		return err
	}

	f, err := os.Open(localPath)
	if err != nil {
		return err
	}
	defer f.Close()

	_, err = client.PutObject(ctx, &s3.PutObjectInput{
		Bucket:      aws.String(u.cfg.S3Bucket),
		Key:         aws.String(objectKey),
		Body:        f,
		ContentType: aws.String("application/gzip"),
	})

	return err
}

func (u *CloudUploader) Download(ctx context.Context, objectKey, localPath string) error {
	client, err := u.client(ctx)
	if err != nil {
		return err
	}

	out, err := client.GetObject(ctx, &s3.GetObjectInput{
		Bucket: aws.String(u.cfg.S3Bucket),
		Key:    aws.String(objectKey),
	})
	if err != nil {
		return err
	}
	defer out.Body.Close()

	if err := os.MkdirAll(filepath.Dir(localPath), 0o755); err != nil {
		return err
	}

	file, err := os.Create(localPath)
	if err != nil {
		return err
	}
	defer file.Close()

	_, err = io.Copy(file, out.Body)
	return err
}
