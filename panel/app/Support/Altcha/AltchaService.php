<?php

namespace App\Support\Altcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\ServerSignature;
use AltchaOrg\Altcha\VerifySolutionOptions;

final class AltchaService
{
    private ?Altcha $client = null;

    private ?Pbkdf2 $algorithm = null;

    public function enabled(): bool
    {
        return (bool) config('panel.altcha.enabled');
    }

    public function driver(): string
    {
        return (string) config('panel.altcha.driver', 'standalone');
    }

    public function widgetChallengeUrl(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($this->driver() === 'sentinel') {
            $url = (string) config('panel.altcha.challenge_url');

            return $url !== '' ? $url : null;
        }

        return route('altcha.challenge');
    }

    /**
     * @return array<string, mixed>
     */
    public function createChallenge(): array
    {
        return $this->client()->createChallenge(new CreateChallengeOptions(
            algorithm: $this->algorithm(),
            cost: 10000,
            expiresAt: time() + 300,
        ))->toArray();
    }

    public function verify(?string $payload): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! filled($payload)) {
            return false;
        }

        try {
            if ($this->usesServerSignature()) {
                return ServerSignature::verifyServerSignature($payload, $this->hmacKey())->verified;
            }

            return $this->client()->verifySolution(new VerifySolutionOptions(
                payload: $payload,
                algorithm: $this->algorithm(),
            ))->verified;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function usesServerSignature(): bool
    {
        return $this->driver() === 'sentinel'
            && (bool) config('panel.altcha.spam_filter');
    }

    private function client(): Altcha
    {
        if ($this->client === null) {
            $this->client = new Altcha(hmacSignatureSecret: $this->hmacKey());
        }

        return $this->client;
    }

    private function algorithm(): Pbkdf2
    {
        return $this->algorithm ??= new Pbkdf2;
    }

    private function hmacKey(): string
    {
        $key = (string) config('panel.altcha.hmac_key');

        if ($key !== '') {
            return $key;
        }

        return hash('sha256', (string) config('app.key'));
    }
}
