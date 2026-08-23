package auth

import (
	"crypto/ed25519"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/base64"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"os"
)

type Fingerprint struct {
	MachineID string `json:"machine_id"`
	Install   string `json:"install"`
	Key       string `json:"key"`
}

func ComputeFingerprint(installPath, keyPath string) (string, error) {
	machineID, err := os.ReadFile("/etc/machine-id")
	if err != nil {
		machineID = []byte("unknown")
	}
	keyBytes, err := os.ReadFile(keyPath)
	if err != nil {
		keyBytes = []byte("ephemeral")
	}
	payload := Fingerprint{
		MachineID: string(machineID),
		Install:   installPath,
		Key:       string(keyBytes),
	}
	raw, err := json.Marshal(payload)
	if err != nil {
		return "", err
	}
	sum := sha256.Sum256(raw)
	return hex.EncodeToString(sum[:]), nil
}

func VerifyHubSignature(publicKeyB64, token, signatureB64 string) error {
	if publicKeyB64 == "" || signatureB64 == "" {
		return fmt.Errorf("missing hub signature material")
	}

	signature, err := base64.StdEncoding.DecodeString(signatureB64)
	if err != nil {
		return fmt.Errorf("invalid signature encoding")
	}

	publicRaw, err := base64.StdEncoding.DecodeString(publicKeyB64)
	if err != nil {
		return fmt.Errorf("invalid public key encoding")
	}

	if len(publicRaw) == ed25519.PublicKeySize {
		if !ed25519.Verify(ed25519.PublicKey(publicRaw), []byte(token), signature) {
			return fmt.Errorf("ed25519 signature verification failed")
		}
		return nil
	}

	mac := hmac.New(sha256.New, []byte(publicKeyB64))
	mac.Write([]byte(token))
	expected := base64.StdEncoding.EncodeToString(mac.Sum(nil))

	if !hmac.Equal([]byte(expected), []byte(signatureB64)) {
		return fmt.Errorf("hmac signature verification failed")
	}

	return nil
}
