package sftp

import (
	"crypto/ed25519"
	"crypto/rand"
	"fmt"
	"io"
	"net"

	"github.com/pkg/sftp"
	"github.com/smelterworks/server-daemon/internal/config"
	"golang.org/x/crypto/ssh"
)

type Server struct {
	cfg      config.Config
	password string
	root     string
}

func New(cfg config.Config, password, root string) *Server {
	return &Server{cfg: cfg, password: password, root: root}
}

func (s *Server) ListenAndServe() error {
	_, priv, err := ed25519.GenerateKey(rand.Reader)
	if err != nil {
		return err
	}
	signer, err := ssh.NewSignerFromKey(priv)
	if err != nil {
		return err
	}

	serverConfig := &ssh.ServerConfig{
		PasswordCallback: func(_ ssh.ConnMetadata, pass []byte) (*ssh.Permissions, error) {
			if s.password == "" || string(pass) != s.password {
				return nil, fmt.Errorf("invalid credentials")
			}
			return nil, nil
		},
	}
	serverConfig.AddHostKey(signer)

	ln, err := net.Listen("tcp", fmt.Sprintf("127.0.0.1:%d", s.cfg.SFTPPort))
	if err != nil {
		return err
	}

	for {
		conn, err := ln.Accept()
		if err != nil {
			return err
		}
		go s.handleConn(conn, serverConfig)
	}
}

func (s *Server) handleConn(conn net.Conn, serverConfig *ssh.ServerConfig) {
	sshConn, chans, reqs, err := ssh.NewServerConn(conn, serverConfig)
	if err != nil {
		_ = conn.Close()
		return
	}
	go ssh.DiscardRequests(reqs)

	for newChannel := range chans {
		if newChannel.ChannelType() != "session" {
			_ = newChannel.Reject(ssh.UnknownChannelType, "unsupported channel type")
			continue
		}
		channel, requests, err := newChannel.Accept()
		if err != nil {
			continue
		}
		go func(in <-chan *ssh.Request) {
			for req := range in {
				ok := false
				if req.Type == "subsystem" && len(req.Payload) >= 4 && string(req.Payload[4:]) == "sftp" {
					ok = true
				}
				_ = req.Reply(ok, nil)
			}
		}(requests)

		server, err := sftp.NewServer(channel, sftp.WithServerWorkingDirectory(s.root))
		if err != nil {
			_ = channel.Close()
			continue
		}
		if err := server.Serve(); err != nil && err != io.EOF {
			_ = server.Close()
		}
		_ = sshConn.Close()
	}
}
