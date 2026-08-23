# SmelterWorks Server Daemon (`smelterd`)

Host agent for Vintage Story servers managed through the SmelterWorks panel.

## Binaries

- `smelterd` long-running agent (hub connection + local API)
- `daemon-tool` break-glass CLI when panel or internet is down

## Build

```bash
cd server-daemon
go build -o bin/smelterd ./cmd/smelterd
go build -o bin/daemon-tool ./cmd/daemon-tool
```

## Configure

| Variable | Purpose |
| --- | --- |
| `SMELTER_HUB_URL` | Panel base URL |
| `SMELTER_TOKEN` | One-time daemon registration token |
| `SMELTER_DATA_PATH` | World data root |
| `SMELTER_MODS_PATH` | Mods bind path |
| `SMELTER_BACKUP_PATH` | Local backup output |
| `SMELTER_DOCKER_IMAGE` | Default `ghcr.io/smelterworks/vs-dockerized-server:latest` |
| `SMELTER_LOCAL_SOCKET` | Unix socket for `daemon-tool` |
| `SMELTER_LOCAL_TOKEN` | Local API auth token |
| `AWS_*` | S3-compatible cloud backups |

## Local control

```bash
export SMELTER_LOCAL_TOKEN=your-token
daemon-tool status
daemon-tool start
daemon-tool backup
```

`daemon-tool` talks only to the local unix socket. It does not open a remote management port.

## Agent security

Beszel-style handshake:

1. Agent presents registration token
2. Panel signs token challenge
3. Agent verifies signature and returns fingerprint
4. Panel authorizes the connection

## Deployment

Run `smelterd` on the host beside `ghcr.io/smelterworks/vs-dockerized-server`. Do not bake the daemon into the game container image.

See `deploy/systemd/smelterd.service` for a unit file template.
