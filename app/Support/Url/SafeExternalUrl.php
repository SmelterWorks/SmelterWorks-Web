<?php

namespace App\Support\Url;

use Uri\Rfc3986\Uri;

final class SafeExternalUrl
{
    public static function httpsOrNull(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        try {
            $uri = Uri::parse(trim($url));
        } catch (\Throwable) {
            return null;
        }

        $scheme = strtolower($uri->getScheme());
        $host = $uri->getHost();

        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        return $uri->toString();
    }
}
