package main

import (
	"context"
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
		if err := callLocal(cfg, "/"+cmd, http.MethodPost); err != nil {
			fmt.Fprintf(os.Stderr, "error: %v\n", err)
			os.Exit(1)
		}
	case "migrate":
		if len(os.Args) < 3 {
			fmt.Fprintln(os.Stderr, "usage: daemon-tool migrate export|import")
			os.Exit(2)
		}
		fmt.Printf("migrate %s: use smelterd RPC or panel UI for remote migrations\n", os.Args[2])
	default:
		printUsage()
		os.Exit(2)
	}
}

func callLocal(cfg config.Config, path, method string) error {
	client := &http.Client{
		Timeout: 30 * time.Second,
		Transport: &http.Transport{
			DialContext: func(_ context.Context, _, _ string) (net.Conn, error) {
				return net.Dial("unix", cfg.LocalSocket)
			},
		},
	}
	req, err := http.NewRequest(method, "http://unix"+path, nil)
	if err != nil {
		return err
	}
	if cfg.LocalToken != "" {
		req.Header.Set("X-Local-Token", cfg.LocalToken)
	}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)
	if resp.StatusCode >= 300 {
		return fmt.Errorf("%s", string(body))
	}
	fmt.Println(string(body))
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
  migrate export|import`)
}
