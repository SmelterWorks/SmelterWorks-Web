<?php

namespace App\Support\Updates;

use App\Support\Url\SafeExternalUrl;
use Uri\Rfc3986\Uri;

final class UpstreamUrlValidator
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public function isAllowed(string $url, array $allowedHosts): bool
    {
        $normalized = SafeExternalUrl::httpsOrNull($url);

        if ($normalized === null) {
            return false;
        }

        try {
            $uri = Uri::parse($normalized);
        } catch (\Throwable) {
            return false;
        }

        $host = strtolower($uri->getHost());

        foreach ($allowedHosts as $allowed) {
            if (strtolower((string) $allowed) === $host) {
                return true;
            }
        }

        return false;
    }
}
