<?php

namespace App\Support\Agent;

use App\Models\DaemonRegistration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AgentHandshakeService
{
    public function __construct(
        private readonly HubKeyService $keys,
    ) {}

    /**
     * @return array{token: string, hub_public_key: string, expires_at: string}
     */
    public function issueToken(DaemonRegistration $daemon): array
    {
        $plain = 'swd_'.Str::random(48);

        $daemon->update([
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 16),
            'token_expires_at' => now()->addMinutes((int) config('panel.agent.token_ttl_minutes', 60)),
            'status' => DaemonRegistration::STATUS_PENDING,
            'fingerprint' => null,
        ]);

        return [
            'token' => $plain,
            'hub_public_key' => $daemon->hub_public_key,
            'expires_at' => $daemon->token_expires_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * @return array{challenge: string, signature: string}
     */
    public function begin(string $token): array
    {
        $daemon = $this->findByToken($token);
        $challenge = base64_encode(random_bytes(32));

        Cache::put($this->challengeKey($daemon->id), $challenge, now()->addMinutes(5));

        return [
            'challenge' => $challenge,
            'signature' => $this->keys->sign($daemon->hub_private_key, $token),
        ];
    }

    /**
     * @return array{status: string, daemon_uuid: string}
     */
    public function complete(string $token, string $fingerprint, string $challenge): array
    {
        $daemon = $this->findByToken($token);
        $expected = Cache::pull($this->challengeKey($daemon->id));

        if ($expected === null || ! hash_equals($expected, $challenge)) {
            throw ValidationException::withMessages([
                'challenge' => 'Invalid or expired challenge.',
            ]);
        }

        if ($daemon->fingerprint !== null && $daemon->fingerprint !== $fingerprint) {
            throw ValidationException::withMessages([
                'fingerprint' => 'Fingerprint mismatch for this daemon.',
            ]);
        }

        $daemon->update([
            'fingerprint' => $fingerprint,
            'status' => DaemonRegistration::STATUS_ACTIVE,
            'last_seen_at' => now(),
            'token_hash' => hash('sha256', Str::random(64)),
            'token_expires_at' => null,
        ]);

        return [
            'status' => 'authorized',
            'daemon_uuid' => $daemon->uuid,
        ];
    }

    public function heartbeat(string $daemonUuid, string $fingerprint): void
    {
        $daemon = DaemonRegistration::query()
            ->where('uuid', $daemonUuid)
            ->where('status', DaemonRegistration::STATUS_ACTIVE)
            ->firstOrFail();

        if (! hash_equals((string) $daemon->fingerprint, $fingerprint)) {
            throw ValidationException::withMessages([
                'fingerprint' => 'Fingerprint mismatch.',
            ]);
        }

        $daemon->update(['last_seen_at' => now()]);
    }

    private function findByToken(string $token): DaemonRegistration
    {
        $daemon = DaemonRegistration::query()
            ->where('token_hash', hash('sha256', $token))
            ->where(function ($query): void {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>', now());
            })
            ->first();

        if ($daemon === null) {
            throw ValidationException::withMessages([
                'token' => 'Invalid or expired registration token.',
            ]);
        }

        if ($daemon->status === DaemonRegistration::STATUS_REVOKED) {
            throw ValidationException::withMessages([
                'token' => 'This daemon registration was revoked.',
            ]);
        }

        return $daemon;
    }

    private function challengeKey(int $daemonId): string
    {
        return 'panel:agent:challenge:'.$daemonId;
    }
}
