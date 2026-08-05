<?php

namespace App\Support\Relic;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Uri\Rfc3986\Uri;

final class RelicGitHubReleases
{
    private const CACHE_SECONDS = 900;

    /**
     * @return array{owner: string, repo: string}|null
     */
    public function parseRepo(string $repoUrl): ?array
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

    /**
     * Newest GitHub prerelease whose tag matches nightly-YYYYMMDD.
     *
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    public function latestNightly(string $owner, string $repo): ?array
    {
        $cacheKey = "relic.github.nightly.{$owner}.{$repo}";

        /** @var array{tag: string, html_url: string, published_at: string|null, assets: list<array{name: string, browser_download_url: string}>}|null|false $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        try {
            $nightly = $this->fetchLatestNightly($owner, $repo);
        } catch (\Throwable $exception) {
            Log::warning('Relic nightly release lookup failed.', [
                'owner' => $owner,
                'repo' => $repo,
                'message' => $exception->getMessage(),
            ]);
            $nightly = null;
        }

        Cache::put($cacheKey, $nightly ?? false, now()->addSeconds(self::CACHE_SECONDS));

        return $nightly;
    }

    /**
     * Newest GitHub stable release (non-prerelease).
     *
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    public function latestStable(string $owner, string $repo): ?array
    {
        $cacheKey = "relic.github.stable.{$owner}.{$repo}";

        /** @var array{tag: string, html_url: string, published_at: string|null, assets: list<array{name: string, browser_download_url: string}>}|null|false $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        try {
            $stable = $this->fetchLatestStable($owner, $repo);
        } catch (\Throwable $exception) {
            Log::warning('Relic stable release lookup failed.', [
                'owner' => $owner,
                'repo' => $repo,
                'message' => $exception->getMessage(),
            ]);
            $stable = null;
        }

        Cache::put($cacheKey, $stable ?? false, now()->addSeconds(self::CACHE_SECONDS));

        return $stable;
    }

    /**
     * @param  list<array{name: string, browser_download_url: string}>  $assets
     */
    public function assetUrlForRid(array $assets, string $rid): ?string
    {
        $candidates = collect($assets)
            ->filter(fn (array $asset): bool => filled($asset['name'] ?? null) && filled($asset['browser_download_url'] ?? null))
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($rid === 'linux-x64') {
            $appImage = $candidates->first(
                fn (array $asset): bool => str_ends_with(strtolower($asset['name']), '.appimage'),
            );
            if ($appImage !== null) {
                return $appImage['browser_download_url'];
            }

            $tar = $candidates->first(
                fn (array $asset): bool => str_contains($asset['name'], 'linux-x64')
                    && str_ends_with($asset['name'], '.tar.gz'),
            );
            if ($tar !== null) {
                return $tar['browser_download_url'];
            }
        }

        if (str_starts_with($rid, 'osx-')) {
            $bundle = $candidates->first(
                fn (array $asset): bool => str_contains($asset['name'], $rid)
                    && str_ends_with($asset['name'], '.app.zip'),
            );
            if ($bundle !== null) {
                return $bundle['browser_download_url'];
            }
        }

        if ($rid === 'win-x64') {
            $zip = $candidates->first(
                fn (array $asset): bool => str_contains($asset['name'], 'win-x64')
                    && str_ends_with($asset['name'], '.zip')
                    && ! str_ends_with($asset['name'], '.app.zip'),
            );
            if ($zip !== null) {
                return $zip['browser_download_url'];
            }
        }

        $fallback = $candidates->first(
            fn (array $asset): bool => str_contains($asset['name'], $rid),
        );

        return $fallback['browser_download_url'] ?? null;
    }

    /**
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    private function fetchLatestNightly(string $owner, string $repo): ?array
    {
        $releases = $this->fetchReleases($owner, $repo);

        if ($releases === null) {
            return null;
        }

        $nightlies = collect($releases)
            ->filter(function (array $release): bool {
                if (! ($release['prerelease'] ?? false)) {
                    return false;
                }

                $tag = (string) ($release['tag_name'] ?? '');

                return (bool) preg_match('/^nightly-\d{8}$/', $tag);
            })
            ->sortByDesc(fn (array $release): string => (string) ($release['tag_name'] ?? ''))
            ->values();

        $latest = $nightlies->first();

        if (! is_array($latest)) {
            return null;
        }

        return $this->normalizeRelease($latest);
    }

    /**
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    private function fetchLatestStable(string $owner, string $repo): ?array
    {
        $releases = $this->fetchReleases($owner, $repo);

        if ($releases === null) {
            return null;
        }

        $stable = collect($releases)
            ->filter(fn (array $release): bool => ! ($release['prerelease'] ?? false))
            ->sortByDesc(fn (array $release): string => (string) ($release['published_at'] ?? ''))
            ->first();

        if (! is_array($stable)) {
            return null;
        }

        return $this->normalizeRelease($stable);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchReleases(string $owner, string $repo): ?array
    {
        $response = Http::timeout(8)
            ->connectTimeout(3)
            ->retry(2, 150)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'SmelterWorks-Web',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->get("https://api.github.com/repos/{$owner}/{$repo}/releases", [
                'per_page' => 30,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $releases = [];

        foreach ($payload as $item) {
            if (is_array($item)) {
                $releases[] = $item;
            }
        }

        return $releases;
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }
     */
    private function normalizeRelease(array $release): array
    {
        $assets = [];
        foreach ($release['assets'] ?? [] as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['browser_download_url'] ?? '');

            if ($name === '' || $url === '') {
                continue;
            }

            $assets[] = [
                'name' => $name,
                'browser_download_url' => $url,
            ];
        }

        return [
            'tag' => (string) $release['tag_name'],
            'html_url' => (string) ($release['html_url'] ?? ''),
            'published_at' => isset($release['published_at']) ? (string) $release['published_at'] : null,
            'assets' => $assets,
        ];
    }
}
