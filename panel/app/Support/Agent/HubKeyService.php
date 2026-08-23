<?php

namespace App\Support\Agent;

final class HubKeyService
{
    /**
     * @return array{public_key: string, private_key: string}
     */
    public function generate(): array
    {
        if (extension_loaded('sodium')) {
            $key = sodium_crypto_sign_keypair();

            return [
                'public_key' => base64_encode(sodium_crypto_sign_publickey($key)),
                'private_key' => base64_encode(sodium_crypto_sign_secretkey($key)),
            ];
        }

        $secret = random_bytes(32);
        $encoded = base64_encode($secret);

        return [
            'public_key' => $encoded,
            'private_key' => $encoded,
        ];
    }

    public function sign(string $privateKeyBase64, string $message): string
    {
        if (extension_loaded('sodium')) {
            $secret = base64_decode($privateKeyBase64, true);

            if ($secret === false) {
                throw new \InvalidArgumentException('Invalid hub private key.');
            }

            return base64_encode(sodium_crypto_sign_detached($message, $secret));
        }

        return base64_encode(hash_hmac('sha256', $message, $privateKeyBase64, true));
    }

    public function verify(string $publicKeyBase64, string $message, string $signatureBase64): bool
    {
        if (extension_loaded('sodium')) {
            $public = base64_decode($publicKeyBase64, true);
            $signature = base64_decode($signatureBase64, true);

            if ($public === false || $signature === false) {
                return false;
            }

            return sodium_crypto_sign_verify_detached($signature, $message, $public);
        }

        $expected = $this->sign($publicKeyBase64, $message);

        return hash_equals($expected, $signatureBase64);
    }
}
