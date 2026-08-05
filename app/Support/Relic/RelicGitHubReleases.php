<?php

namespace App\Support\Relic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Uri\Rfc3986\Uri;

final class RelicGitHubReleases
{
    private const RATE_LIMIT_CACHE_SECONDS = 1800;

    /**
     * @var array<string, list<array<string, mixed>>|null>
     */
    private array $memoryReleases = [];

    /**
     * @var array<string, array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null>
     */
    private array $memoryStable = [];

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
        $releases = $this->releasesList($owner, $repo);

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
     * Latest published stable release from GitHub or Forgejo.
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
        $memoryKey = "{$owner}/{$repo}";

        if (array_key_exists($memoryKey, $this->memoryStable)) {
            return $this->memoryStable[$memoryKey];
        }

        $cacheKey = "relic.releases.latest.{$owner}.{$repo}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $this->isFreshCache($cached)) {
            /** @var array{tag: string, html_url: string, published_at: string|null, assets: list<array{name: string, browser_download_url: string}>}|null|false $release */
            $release = $cached['release'] ?? null;

            return $this->memoryStable[$memoryKey] = ($release === false ? null : $release);
        }

        $fetched = $this->fetchLatestStableRelease($owner, $repo);

        if ($fetched !== null) {
            $this->rememberLatestStable($cacheKey, $fetched);

            return $this->memoryStable[$memoryKey] = $fetched;
        }

        if (is_array($cached) && $this->isStaleLatestStableUsable($cached)) {
            Log::warning('Relic stable release lookup failed. Serving stale cache.', [
                'owner' => $owner,
                'repo' => $repo,
            ]);

            /** @var array{tag: string, html_url: string, published_at: string|null, assets: list<array{name: string, browser_download_url: string}>} $release */
            $release = $cached['release'];

            return $this->memoryStable[$memoryKey] = $release;
        }

        Log::warning('Relic stable release lookup failed.', [
            'owner' => $owner,
            'repo' => $repo,
        ]);

        Cache::put($cacheKey, [
            'release' => false,
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds(self::RATE_LIMIT_CACHE_SECONDS));

        return $this->memoryStable[$memoryKey] = null;
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
    private function fetchLatestStableRelease(string $owner, string $repo): ?array
    {
        $github = $this->fetchGitHubLatestStable($owner, $repo);

        if ($github !== null) {
            return $github;
        }

        return $this->fetchForgejoLatestStable();
    }

    /**
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    private function fetchGitHubLatestStable(string $owner, string $repo): ?array
    {
        try {
            $response = $this->githubClient()
                ->get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest");
        } catch (ConnectionException) {
            return null;
        }

        if ($this->isRateLimited($response)) {
            return null;
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->releaseFromPayload($response->json());
    }

    /**
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    private function fetchForgejoLatestStable(): ?array
    {
        $repoUrl = $this->forgejoReleasesRepoUrl();

        if (! filled($repoUrl)) {
            return null;
        }

        $parsed = $this->parseRepo((string) $repoUrl);

        if ($parsed === null) {
            return null;
        }

        try {
            $uri = Uri::parse(trim((string) $repoUrl));
        } catch (\Throwable) {
            return null;
        }

        $apiBase = $uri->getScheme().'://'.$uri->getHost();
        $endpoint = "{$apiBase}/api/v1/repos/{$parsed['owner']}/{$parsed['repo']}/releases/latest";

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'SmelterWorks-Web',
                ])
                ->get($endpoint);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->releaseFromPayload($response->json());
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function releasesList(string $owner, string $repo): ?array
    {
        $memoryKey = "{$owner}/{$repo}";

        if (array_key_exists($memoryKey, $this->memoryReleases)) {
            return $this->memoryReleases[$memoryKey];
        }

        $cacheKey = "relic.releases.{$owner}.{$repo}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $this->isFreshCache($cached)) {
            /** @var list<array<string, mixed>>|null $releases */
            $releases = $cached['releases'] ?? null;

            return $this->memoryReleases[$memoryKey] = $releases;
        }

        $fetched = $this->fetchReleases($owner, $repo);

        if ($fetched !== null) {
            $this->rememberReleases($cacheKey, $fetched);

            return $this->memoryReleases[$memoryKey] = $fetched;
        }

        if (is_array($cached) && $this->isStaleCacheUsable($cached)) {
            Log::warning('Relic release lookup failed. Serving stale cache.', [
                'owner' => $owner,
                'repo' => $repo,
            ]);

            /** @var list<array<string, mixed>>|null $releases */
            $releases = $cached['releases'] ?? null;

            return $this->memoryReleases[$memoryKey] = $releases;
        }

        Log::warning('Relic release lookup failed.', [
            'owner' => $owner,
            'repo' => $repo,
        ]);

        Cache::put($cacheKey, [
            'releases' => null,
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds(self::RATE_LIMIT_CACHE_SECONDS));

        return $this->memoryReleases[$memoryKey] = null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchReleases(string $owner, string $repo): ?array
    {
        $github = $this->fetchGitHubReleases($owner, $repo);

        if ($github !== null) {
            return $github;
        }

        return $this->fetchForgejoReleases();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchGitHubReleases(string $owner, string $repo): ?array
    {
        try {
            $response = $this->githubClient()
                ->get("https://api.github.com/repos/{$owner}/{$repo}/releases", [
                    'per_page' => 30,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($this->isRateLimited($response)) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->releasesFromPayload($response->json());
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchForgejoReleases(): ?array
    {
        $repoUrl = $this->forgejoReleasesRepoUrl();

        if (! filled($repoUrl)) {
            return null;
        }

        $parsed = $this->parseRepo((string) $repoUrl);

        if ($parsed === null) {
            return null;
        }

        try {
            $uri = Uri::parse(trim((string) $repoUrl));
        } catch (\Throwable) {
            return null;
        }

        $apiBase = $uri->getScheme().'://'.$uri->getHost();
        $endpoint = "{$apiBase}/api/v1/repos/{$parsed['owner']}/{$parsed['repo']}/releases";

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'SmelterWorks-Web',
                ])
                ->get($endpoint, [
                    'limit' => 30,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->releasesFromPayload($response->json());
    }

    private function forgejoReleasesRepoUrl(): ?string
    {
        $explicit = config('smelterworks.relic.releases.forgejo_repo_url');

        if (filled($explicit)) {
            return (string) $explicit;
        }

        $forgejo = rtrim((string) config('smelterworks.links.forgejo'), '/');

        if ($forgejo === '') {
            return null;
        }

        return $forgejo.'/Relic-Launcher';
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function releasesFromPayload(mixed $payload): ?array
    {
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
     * @return array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null
     */
    private function releaseFromPayload(mixed $payload): ?array
    {
        if (! is_array($payload) || ! isset($payload['tag_name'])) {
            return null;
        }

        return $this->normalizeRelease($payload);
    }

    /**
     * @param  list<array<string, mixed>>  $releases
     */
    private function rememberReleases(string $cacheKey, array $releases): void
    {
        Cache::put($cacheKey, [
            'releases' => $releases,
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds($this->cacheSeconds()));
    }

    /**
     * @param  array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }  $release
     */
    private function rememberLatestStable(string $cacheKey, array $release): void
    {
        Cache::put($cacheKey, [
            'release' => $release,
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds($this->cacheSeconds()));
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isFreshCache(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0 && (now()->getTimestamp() - $cachedAt) <= $this->cacheSeconds();
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isStaleCacheUsable(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0
            && array_key_exists('releases', $cached)
            && is_array($cached['releases'])
            && (now()->getTimestamp() - $cachedAt) <= $this->staleCacheSeconds();
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isStaleLatestStableUsable(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0
            && isset($cached['release'])
            && is_array($cached['release'])
            && (now()->getTimestamp() - $cachedAt) <= $this->staleCacheSeconds();
    }

    private function cacheSeconds(): int
    {
        return max(60, (int) config('smelterworks.relic.releases.cache_seconds', 3600));
    }

    private function staleCacheSeconds(): int
    {
        return max($this->cacheSeconds(), (int) config('smelterworks.relic.releases.stale_seconds', 86400));
    }

    private function githubClient(): PendingRequest
    {
        $request = Http::timeout(8)
            ->connectTimeout(3)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'SmelterWorks-Web',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->retry(2, 150, function (\Throwable $exception): bool {
                if ($exception instanceof RequestException) {
                    $status = $exception->response?->status();

                    if (in_array($status, [403, 429], true)) {
                        return false;
                    }
                }

                return true;
            }, throw: false);

        $token = config('smelterworks.relic.releases.github_token');

        if (filled($token)) {
            $request = $request->withToken((string) $token);
        }

        return $request;
    }

    private function isRateLimited(Response $response): bool
    {
        return in_array($response->status(), [403, 429], true);
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
