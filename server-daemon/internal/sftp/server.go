package sftp

import (
	"crypto/ed25519"
	"crypto/rand"
	"fmt"
	"net"

	"github.com/smelterworks/server-daemon/internal/config"
	"golang.org/x/crypto/ssh"
)

type Server struct {
	cfg      config.Config
	password string
}

func New(cfg config.Config, password string) *Server {
	return &Server{cfg: cfg, password: password}
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
			if string(pass) != s.password {
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
		go func(c net.Conn) {
			_, _, _, _ = ssh.NewServerConn(c, serverConfig)
		}(conn)
	}
}
