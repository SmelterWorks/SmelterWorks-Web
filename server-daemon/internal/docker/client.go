package docker

import (
	"bytes"
	"context"
	"fmt"
	"os/exec"
	"strings"
)

type Client struct {
	image         string
	containerName string
}

func New(image, containerName string) *Client {
	return &Client{image: image, containerName: containerName}
}

func (c *Client) Status(ctx context.Context) (string, error) {
	out, err := exec.CommandContext(ctx, "docker", "inspect", "-f", "{{.State.Status}}", c.containerName).CombinedOutput()
	if err != nil {
		return "missing", nil
	}
	return strings.TrimSpace(string(out)), nil
}

func (c *Client) Start(ctx context.Context, dataPath, modsPath, backupPath string) error {
	status, _ := c.Status(ctx)
	if status == "running" {
		return nil
	}
	if status != "missing" {
		_ = exec.CommandContext(ctx, "docker", "start", c.containerName).Run()
		return nil
	}
	args := []string{
		"run", "-d",
		"--name", c.containerName,
		"--restart", "unless-stopped",
		"-p", "42420:42420/tcp",
		"-p", "42420:42420/udp",
		"-v", dataPath + ":/data",
		"-v", modsPath + ":/mods:ro",
		"-v", backupPath + ":/backups",
		c.image,
	}
	cmd := exec.CommandContext(ctx, "docker", args...)
	var stderr bytes.Buffer
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		return fmt.Errorf("docker run: %w: %s", err, stderr.String())
	}
	return nil
}

func (c *Client) Stop(ctx context.Context) error {
	return exec.CommandContext(ctx, "docker", "stop", c.containerName).Run()
}

func (c *Client) Restart(ctx context.Context) error {
	return exec.CommandContext(ctx, "docker", "restart", c.containerName).Run()
}

func (c *Client) Logs(ctx context.Context, tail string) (string, error) {
	out, err := exec.CommandContext(ctx, "docker", "logs", "--tail", tail, c.containerName).CombinedOutput()
	return string(out), err
}

func (c *Client) Exec(ctx context.Context, command ...string) (string, error) {
	args := append([]string{"exec", c.containerName}, command...)
	out, err := exec.CommandContext(ctx, "docker", args...).CombinedOutput()
	return string(out), err
}
