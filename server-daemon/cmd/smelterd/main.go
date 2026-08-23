package main

import (
	"context"
	"log"
	"os"
	"time"

	"github.com/smelterworks/server-daemon/internal/auth"
	"github.com/smelterworks/server-daemon/internal/backup"
	"github.com/smelterworks/server-daemon/internal/config"
	"github.com/smelterworks/server-daemon/internal/docker"
	"github.com/smelterworks/server-daemon/internal/hubclient"
	"github.com/smelterworks/server-daemon/internal/localapi"
)

func main() {
	cfg := config.Load()
	dockerClient := docker.New(cfg.DockerImage, cfg.ContainerName)
	backupSvc := backup.New(cfg)
	local := localapi.New(cfg, dockerClient, backupSvc)

	go func() {
		if err := local.Serve(); err != nil {
			log.Printf("local api stopped: %v", err)
		}
	}()

	fp, err := auth.ComputeFingerprint("/var/lib/smelterd", "/var/lib/smelterd/key")
	if err != nil {
		log.Printf("fingerprint warning: %v", err)
		fp = "unknown"
	}

	if cfg.Token != "" {
		hub := hubclient.New(cfg, fp)
		if err := hub.Connect(context.Background()); err != nil {
			log.Printf("hub connect failed (offline mode): %v", err)
		} else {
			go heartbeatLoop(hub)
		}
	}

	log.Printf("smelterd listening on %s", cfg.LocalSocket)
	select {}
}

func heartbeatLoop(hub *hubclient.Client) {
	ticker := time.NewTicker(30 * time.Second)
	for range ticker.C {
		if err := hub.Heartbeat(context.Background()); err != nil {
			log.Printf("heartbeat failed: %v", err)
		}
	}
}

func init() {
	log.SetOutput(os.Stderr)
	log.SetFlags(log.LstdFlags | log.Lshortfile)
}
