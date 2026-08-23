# syntax=docker/dockerfile:1

FROM golang:1.24-alpine AS build
WORKDIR /src
RUN apk add --no-cache git
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -ldflags="-s -w" -o /out/smelterd ./cmd/smelterd \
    && CGO_ENABLED=0 go build -ldflags="-s -w" -o /out/daemon-tool ./cmd/daemon-tool

FROM alpine:3.21
RUN apk add --no-cache docker-cli ca-certificates \
    && adduser -D -u 1000 smelter \
    && mkdir -p /data /mods /backups /var/lib/smelterd /run \
    && chown -R smelter:smelter /data /mods /backups /var/lib/smelterd /run

COPY --from=build /out/smelterd /out/daemon-tool /usr/local/bin/

USER smelter
ENV SMELTER_HUB_URL=http://panel:8080 \
    SMELTER_DATA_PATH=/data \
    SMELTER_MODS_PATH=/mods \
    SMELTER_BACKUP_PATH=/backups \
    SMELTER_LOCAL_SOCKET=/run/smelterd.sock \
    SMELTER_INSTALL_PATH=/var/lib/smelterd

ENTRYPOINT ["/usr/local/bin/smelterd"]
