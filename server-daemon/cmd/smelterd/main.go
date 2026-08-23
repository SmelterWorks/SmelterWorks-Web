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

	if err := auth.EnsureInstallKey(cfg.InstallPath, cfg.KeyPath); err != nil {
		log.Printf("install key warning: %v", err)
	}

	fp, err := auth.ComputeFingerprint(cfg.InstallPath, cfg.KeyPath)
	if err != nil {
		log.Printf("fingerprint warning: %v", err)
		fp = "unknown"
	}

	executor := hubclient.NewExecutor(cfg, dockerClient, backupSvc)

	if cfg.Token != "" {
		go hubLoop(cfg, fp, dockerClient, executor)
	}

	log.Printf("smelterd listening on %s", cfg.LocalSocket)
	select {}
}

func hubLoop(cfg config.Config, fp string, dockerClient *docker.Client, executor *hubclient.Executor) {
	backoff := 5 * time.Second

	for {
		hub := hubclient.New(cfg, fp)
		if err := hub.Connect(context.Background()); err != nil {
			log.Printf("hub connect failed (retry in %s): %v", backoff, err)
			time.Sleep(backoff)
			if backoff < time.Minute {
				backoff *= 2
			}
			continue
		}

		log.Printf("hub connected as %s", hub.DaemonUUID())
		backoff = 5 * time.Second

		ticker := time.NewTicker(15 * time.Second)
		for range ticker.C {
			status, _ := dockerClient.Status(context.Background())

			if err := hub.Heartbeat(context.Background(), status); err != nil {
				log.Printf("heartbeat failed: %v", err)
				ticker.Stop()
				break
			}

			commands, err := hub.Poll(context.Background())
			if err != nil {
				log.Printf("poll failed: %v", err)
				continue
			}

			for _, command := range commands {
				result, runErr := executor.Run(context.Background(), command)
				status := "completed"
				errMsg := ""
				if runErr != nil {
					status = "failed"
					errMsg = runErr.Error()
					log.Printf("command %s failed: %v", command.UUID, runErr)
				}
				if ackErr := hub.Acknowledge(context.Background(), command.UUID, status, result, errMsg); ackErr != nil {
					log.Printf("ack failed: %v", ackErr)
				}
			}
		}
	}
}

func init() {
	log.SetOutput(os.Stderr)
	log.SetFlags(log.LstdFlags | log.Lshortfile)
}
