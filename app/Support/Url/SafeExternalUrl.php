<?php

namespace App\Support\Url;

final class SafeExternalUrl
{
    public static function httpsOrNull(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        return $url;
    }
}
