<?php

namespace App\Support\Altcha;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeOptions;

final class AltchaService
{
    private ?Altcha $client = null;

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
     * @return array<string, int|string>
     */
    public function createChallenge(): array
    {
        $challenge = $this->client()->createChallenge(new ChallengeOptions(
            expires: (new \DateTimeImmutable)->add(new \DateInterval('PT5M')),
        ));

        return $this->challengeToArray($challenge);
    }

    public function verify(?string $payload): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! filled($payload)) {
            return false;
        }

        if ($this->usesServerSignature()) {
            return $this->client()->verifyServerSignature($payload)->verified;
        }

        return $this->client()->verifySolution($payload, true);
    }

    private function usesServerSignature(): bool
    {
        return $this->driver() === 'sentinel'
            && (bool) config('panel.altcha.spam_filter');
    }

    private function client(): Altcha
    {
        if ($this->client === null) {
            $this->client = new Altcha($this->hmacKey());
        }

        return $this->client;
    }

    private function hmacKey(): string
    {
        $key = (string) config('panel.altcha.hmac_key');

        if ($key !== '') {
            return $key;
        }

        return hash('sha256', (string) config('app.key'));
    }

    /**
     * @return array<string, int|string>
     */
    private function challengeToArray(Challenge $challenge): array
    {
        return [
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'maxnumber' => $challenge->maxNumber,
            'salt' => $challenge->salt,
            'signature' => $challenge->signature,
        ];
    }
}
