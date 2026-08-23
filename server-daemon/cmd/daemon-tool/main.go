package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"time"

	"github.com/smelterworks/server-daemon/internal/config"
)

func main() {
	if len(os.Args) < 2 {
		printUsage()
		os.Exit(2)
	}
	cfg := config.Load()
	cmd := os.Args[1]
	switch cmd {
	case "status", "start", "stop", "restart", "backup":
		if err := callLocal(cfg, "/"+cmd, http.MethodPost, nil); err != nil {
			fmt.Fprintf(os.Stderr, "error: %v\n", err)
			os.Exit(1)
		}
	case "migrate":
		if len(os.Args) < 3 {
			fmt.Fprintln(os.Stderr, "usage: daemon-tool migrate export|import")
			os.Exit(2)
		}
		sub := os.Args[2]
		switch sub {
		case "export":
			body := map[string]string{
				"server_uuid": "local",
			}
			if err := callLocal(cfg, "/migrate/export", http.MethodPost, body); err != nil {
				fmt.Fprintf(os.Stderr, "error: %v\n", err)
				os.Exit(1)
			}
		case "import":
			packagePath := ""
			if len(os.Args) > 3 {
				packagePath = os.Args[3]
			}
			body := map[string]string{
				"package_path": packagePath,
			}
			if err := callLocal(cfg, "/migrate/import", http.MethodPost, body); err != nil {
				fmt.Fprintf(os.Stderr, "error: %v\n", err)
				os.Exit(1)
			}
		default:
			fmt.Fprintln(os.Stderr, "usage: daemon-tool migrate export|import [package_path]")
			os.Exit(2)
		}
	default:
		printUsage()
		os.Exit(2)
	}
}

func callLocal(cfg config.Config, path, method string, body map[string]string) error {
	client := &http.Client{
		Timeout: 30 * time.Minute,
		Transport: &http.Transport{
			DialContext: func(_ context.Context, _, _ string) (net.Conn, error) {
				return net.Dial("unix", cfg.LocalSocket)
			},
		},
	}
	var reader io.Reader
	if body != nil {
		raw, _ := json.Marshal(body)
		reader = bytes.NewReader(raw)
	}
	req, err := http.NewRequest(method, "http://unix"+path, reader)
	if err != nil {
		return err
	}
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}
	if cfg.LocalToken != "" {
		req.Header.Set("X-Local-Token", cfg.LocalToken)
	}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	raw, _ := io.ReadAll(resp.Body)
	if resp.StatusCode >= 300 {
		return fmt.Errorf("%s", string(raw))
	}
	fmt.Println(string(raw))
	return nil
}

func printUsage() {
	fmt.Println(`daemon-tool - local break-glass control for smelterd

Commands:
  status
  start
  stop
  restart
  backup
  migrate export
  migrate import [package_path]`)
}
