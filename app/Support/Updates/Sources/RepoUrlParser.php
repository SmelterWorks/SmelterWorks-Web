<?php

namespace App\Support\Updates\Sources;

use Uri\Rfc3986\Uri;

final class RepoUrlParser
{
    /**
     * @return array{owner: string, repo: string}|null
     */
    public function parse(string $repoUrl): ?array
    {
        try {
            $uri = Uri::parse(trim($repoUrl));
        } catch (\Throwable) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($uri->getPath(), '/'))));

        if (count($segments) < 2) {
            return null;
        }

        return [
            'owner' => $segments[0],
            'repo' => $segments[1],
        ];
    }

    public function apiBase(string $repoUrl): ?string
    {
        try {
            $uri = Uri::parse(trim($repoUrl));
        } catch (\Throwable) {
            return null;
        }

        $scheme = strtolower($uri->getScheme());
        $host = $uri->getHost();

        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        return $scheme.'://'.$host;
    }
}
