package auth

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/base64"
	"testing"
)

func TestVerifyHubSignatureHMACFallback(t *testing.T) {
	secret := "dGVzdC1zZWNyZXQta2V5LWZvci1obWFjLWZhbGxiYWNr"
	token := "swd_test_token_value"

	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write([]byte(token))
	sig := base64.StdEncoding.EncodeToString(mac.Sum(nil))

	if err := VerifyHubSignature(secret, token, sig); err != nil {
		t.Fatalf("expected valid hmac signature, got %v", err)
	}

	if err := VerifyHubSignature(secret, token, "bad-signature"); err == nil {
		t.Fatal("expected invalid signature to fail")
	}
}
