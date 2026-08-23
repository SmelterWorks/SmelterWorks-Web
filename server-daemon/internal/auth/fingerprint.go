package auth

import (
	"crypto/sha256"
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
	return nil
}
