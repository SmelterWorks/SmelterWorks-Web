package hubclient

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"

	"github.com/smelterworks/server-daemon/internal/config"
)

type Client struct {
	cfg    config.Config
	http   *http.Client
	fp     string
	daemon string
}

func New(cfg config.Config, fingerprint string) *Client {
	return &Client{
		cfg: cfg,
		http: &http.Client{
			Timeout: 30 * time.Second,
		},
		fp: fingerprint,
	}
}

func (c *Client) Connect(ctx context.Context) error {
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.cfg.HubURL+"/api/v1/agent/connect", nil)
	if err != nil {
		return err
	}
	req.Header.Set("X-Agent-Token", c.cfg.Token)
	resp, err := c.http.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)
	if resp.StatusCode >= 300 {
		return fmt.Errorf("connect failed: %s", string(body))
	}
	var payload struct {
		Challenge string `json:"challenge"`
		Signature string `json:"signature"`
	}
	if err := json.Unmarshal(body, &payload); err != nil {
		return err
	}
	completeBody, _ := json.Marshal(map[string]string{
		"token":       c.cfg.Token,
		"fingerprint": c.fp,
		"challenge":   payload.Challenge,
	})
	completeReq, err := http.NewRequestWithContext(ctx, http.MethodPost, c.cfg.HubURL+"/api/v1/agent/complete", bytes.NewReader(completeBody))
	if err != nil {
		return err
	}
	completeReq.Header.Set("Content-Type", "application/json")
	completeResp, err := c.http.Do(completeReq)
	if err != nil {
		return err
	}
	defer completeResp.Body.Close()
	completeRaw, _ := io.ReadAll(completeResp.Body)
	if completeResp.StatusCode >= 300 {
		return fmt.Errorf("complete failed: %s", string(completeRaw))
	}
	var done struct {
		DaemonUUID string `json:"daemon_uuid"`
	}
	if err := json.Unmarshal(completeRaw, &done); err != nil {
		return err
	}
	c.daemon = done.DaemonUUID
	return nil
}

func (c *Client) Heartbeat(ctx context.Context) error {
	if c.daemon == "" {
		return fmt.Errorf("not connected")
	}
	body, _ := json.Marshal(map[string]string{
		"daemon_uuid": c.daemon,
		"fingerprint": c.fp,
	})
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.cfg.HubURL+"/api/v1/agent/heartbeat", bytes.NewReader(body))
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")
	resp, err := c.http.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 300 {
		raw, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("heartbeat failed: %s", string(raw))
	}
	return nil
}
