<?php

namespace App\Support\Security;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

final class TotpService
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function provisioningUri(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('panel.name'),
            $user->email,
            $secret,
        );
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->totp_enabled || $user->totp_secret === null) {
            return false;
        }

        return $this->google2fa->verifyKey($user->totp_secret, $code);
    }

    public function verifyPending(User $user, string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function recoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::upper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }
}
