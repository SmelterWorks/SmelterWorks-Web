<?php

namespace App\Support\Security;

final class SessionFingerprint
{
    public function ipSubnet(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 4)).'::/64';
        }

        $octets = explode('.', $ip);

        if (count($octets) !== 4) {
            return $ip;
        }

        return $octets[0].'.'.$octets[1].'.'.$octets[2].'.0/24';
    }

    public function userAgentFamily(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        foreach (['firefox', 'chrome', 'safari', 'edge', 'relic'] as $family) {
            if (str_contains($ua, $family)) {
                return $family;
            }
        }

        return 'other';
    }
}
