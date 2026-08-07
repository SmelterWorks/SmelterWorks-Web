<?php

namespace App\Support\Updates\Sources;

use App\Support\Updates\Data\UpstreamAsset;
use App\Support\Updates\Data\UpstreamRelease;
use App\Support\Updates\UpdateProductRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GitHubReleaseSource implements UpdateSource
{
    private const NEGATIVE_CACHE_SECONDS = 1800;

    /**
     * @var array<string, UpstreamRelease|null>
     */
    private array $memory = [];

    public function __construct(
        private readonly UpdateProductRegistry $registry,
        private readonly RepoUrlParser $parser,
    ) {}

    public function fetchChannel(string $productSlug, string $channelSlug): ?UpstreamRelease
    {
        $memoryKey = "{$productSlug}.{$channelSlug}";

        if (array_key_exists($memoryKey, $this->memory)) {
            return $this->memory[$memoryKey];
        }

        $product = $this->registry->product($productSlug);
        $channel = $this->registry->channel($productSlug, $channelSlug);

        if ($product === null || $channel === null) {
            return $this->memory[$memoryKey] = null;
        }

        $cacheKey = "updates.upstream.{$productSlug}.{$channelSlug}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $this->isFreshCache($cached)) {
            /** @var array<string, mixed>|null|false $payload */
            $payload = $cached['release'] ?? null;

            return $this->memory[$memoryKey] = $this->releaseFromCachePayload($payload);
        }

        $fetched = $this->fetchUpstream($product, $channel);

        if ($fetched !== null) {
            $this->rememberRelease($cacheKey, $fetched);

            return $this->memory[$memoryKey] = $fetched;
        }

        if (is_array($cached) && $this->isStaleCacheUsable($cached)) {
            Log::warning('Update upstream lookup failed. Serving stale cache.', [
                'product' => $productSlug,
                'channel' => $channelSlug,
            ]);

            /** @var array<string, mixed> $payload */
            $payload = $cached['release'];

            return $this->memory[$memoryKey] = $this->releaseFromStoragePayload($payload);
        }

        Log::warning('Update upstream lookup failed.', [
            'product' => $productSlug,
            'channel' => $channelSlug,
        ]);

        Cache::put($cacheKey, [
            'release' => false,
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds(self::NEGATIVE_CACHE_SECONDS));

        return $this->memory[$memoryKey] = null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $channel
     */
    private function fetchUpstream(array $product, array $channel): ?UpstreamRelease
    {
        /** @var array<string, mixed> $source */
        $source = $product['source'] ?? [];
        $repoUrl = (string) ($source['repo_url'] ?? '');
        $parsed = $this->parser->parse($repoUrl);

        if ($parsed === null) {
            return null;
        }

        $selector = (string) ($channel['selector'] ?? '');

        if ($selector === 'latest_stable') {
            $github = $this->fetchGitHubLatestStable($parsed['owner'], $parsed['repo'], $source);

            if ($github !== null) {
                return $github;
            }

            return $this->fetchForgejoLatestStable($source);
        }

        if ($selector === 'latest_prerelease_tag') {
            $tagPattern = (string) ($channel['tag_pattern'] ?? '');
            $github = $this->fetchGitHubPrereleaseByTag($parsed['owner'], $parsed['repo'], $source, $tagPattern);

            if ($github !== null) {
                return $github;
            }

            return $this->fetchForgejoPrereleaseByTag($source, $tagPattern);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fetchGitHubLatestStable(string $owner, string $repo, array $source): ?UpstreamRelease
    {
        try {
            $response = $this->githubClient($source)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest");
        } catch (ConnectionException) {
            return null;
        }

        if ($this->isRateLimited($response) || $response->status() === 404 || ! $response->successful()) {
            return null;
        }

        return $this->releaseFromPayload($response->json());
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fetchGitHubPrereleaseByTag(
        string $owner,
        string $repo,
        array $source,
        string $tagPattern,
    ): ?UpstreamRelease {
        $releases = $this->fetchGitHubReleasesList($owner, $repo, $source);

        if ($releases === null) {
            return null;
        }

        return $this->pickPrereleaseByTag($releases, $tagPattern);
    }

    /**
     * @param  array<string, mixed>  $source
     * @return list<array<string, mixed>>|null
     */
    private function fetchGitHubReleasesList(string $owner, string $repo, array $source): ?array
    {
        try {
            $response = $this->githubClient($source)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/releases", [
                    'per_page' => 30,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($this->isRateLimited($response) || ! $response->successful()) {
            return null;
        }

        return $this->releasesFromPayload($response->json());
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fetchForgejoLatestStable(array $source): ?UpstreamRelease
    {
        $repoUrl = $this->forgejoRepoUrl($source);
        $parsed = $repoUrl !== null ? $this->parser->parse($repoUrl) : null;
        $apiBase = $repoUrl !== null ? $this->parser->apiBase($repoUrl) : null;

        if ($parsed === null || $apiBase === null) {
            return null;
        }

        $endpoint = "{$apiBase}/api/v1/repos/{$parsed['owner']}/{$parsed['repo']}/releases/latest";

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'SmelterWorks-Web'])
                ->get($endpoint);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() === 404 || ! $response->successful()) {
            return null;
        }

        return $this->releaseFromPayload($response->json());
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fetchForgejoPrereleaseByTag(array $source, string $tagPattern): ?UpstreamRelease
    {
        $repoUrl = $this->forgejoRepoUrl($source);
        $parsed = $repoUrl !== null ? $this->parser->parse($repoUrl) : null;
        $apiBase = $repoUrl !== null ? $this->parser->apiBase($repoUrl) : null;

        if ($parsed === null || $apiBase === null) {
            return null;
        }

        $endpoint = "{$apiBase}/api/v1/repos/{$parsed['owner']}/{$parsed['repo']}/releases";

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'SmelterWorks-Web'])
                ->get($endpoint, ['limit' => 30]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $releases = $this->releasesFromPayload($response->json());

        if ($releases === null) {
            return null;
        }

        return $this->pickPrereleaseByTag($releases, $tagPattern);
    }

    /**
     * @param  list<array<string, mixed>>  $releases
     */
    private function pickPrereleaseByTag(array $releases, string $tagPattern): ?UpstreamRelease
    {
        if ($tagPattern === '') {
            return null;
        }

        $latest = collect($releases)
            ->filter(function (array $release): bool {
                if (! ($release['prerelease'] ?? false)) {
                    return false;
                }

                $tag = (string) ($release['tag_name'] ?? '');

                return $tag !== '';
            })
            ->filter(function (array $release) use ($tagPattern): bool {
                $tag = (string) ($release['tag_name'] ?? '');

                return (bool) preg_match($tagPattern, $tag);
            })
            ->sortByDesc(fn (array $release): string => (string) ($release['tag_name'] ?? ''))
            ->first();

        if (! is_array($latest)) {
            return null;
        }

        return $this->releaseFromPayload($latest);
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function forgejoRepoUrl(array $source): ?string
    {
        $explicit = $source['forgejo_repo_url'] ?? null;

        if (filled($explicit)) {
            return (string) $explicit;
        }

        $forgejo = rtrim((string) config('smelterworks.links.forgejo'), '/');

        if ($forgejo === '') {
            return null;
        }

        $repoUrl = (string) ($source['repo_url'] ?? '');
        $parsed = $this->parser->parse($repoUrl);

        if ($parsed === null) {
            return null;
        }

        return $forgejo.'/'.$parsed['repo'];
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

    private function releaseFromPayload(mixed $payload): ?UpstreamRelease
    {
        if (! is_array($payload) || ! isset($payload['tag_name'])) {
            return null;
        }

        $assets = [];

        foreach ($payload['assets'] ?? [] as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['browser_download_url'] ?? '');

            if ($name === '' || $url === '') {
                continue;
            }

            $assets[] = new UpstreamAsset(name: $name, downloadUrl: $url);
        }

        $tag = (string) $payload['tag_name'];

        return new UpstreamRelease(
            tag: $tag,
            version: $this->normalizeVersion($tag),
            htmlUrl: (string) ($payload['html_url'] ?? ''),
            publishedAt: isset($payload['published_at']) ? (string) $payload['published_at'] : null,
            assets: $assets,
        );
    }

    private function normalizeVersion(string $tag): string
    {
        if (preg_match('/^nightly-\d{8}$/', $tag)) {
            return $tag;
        }

        if (preg_match('/^v\d/', $tag)) {
            return ltrim($tag, 'v');
        }

        return $tag;
    }

    /**
     * @param  array<string, mixed>|null|false  $payload
     */
    private function releaseFromCachePayload(mixed $payload): ?UpstreamRelease
    {
        if ($payload === false || $payload === null) {
            return null;
        }

        return $this->releaseFromStoragePayload($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function releaseFromStoragePayload(array $payload): ?UpstreamRelease
    {
        $tag = (string) ($payload['tag'] ?? '');

        if ($tag === '') {
            return null;
        }

        $assets = [];

        foreach ($payload['assets'] ?? [] as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['download_url'] ?? '');

            if ($name === '' || $url === '') {
                continue;
            }

            $assets[] = new UpstreamAsset(name: $name, downloadUrl: $url);
        }

        return new UpstreamRelease(
            tag: $tag,
            version: (string) ($payload['version'] ?? $this->normalizeVersion($tag)),
            htmlUrl: (string) ($payload['html_url'] ?? ''),
            publishedAt: isset($payload['published_at']) ? (string) $payload['published_at'] : null,
            assets: $assets,
        );
    }

    private function rememberRelease(string $cacheKey, UpstreamRelease $release): void
    {
        Cache::put($cacheKey, [
            'release' => [
                'tag' => $release->tag,
                'version' => $release->version,
                'html_url' => $release->htmlUrl,
                'published_at' => $release->publishedAt,
                'assets' => array_map(
                    fn (UpstreamAsset $asset): array => [
                        'name' => $asset->name,
                        'download_url' => $asset->downloadUrl,
                    ],
                    $release->assets,
                ),
            ],
            'cached_at' => now()->getTimestamp(),
        ], now()->addSeconds($this->registry->cacheSeconds()));
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isFreshCache(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0 && (now()->getTimestamp() - $cachedAt) <= $this->registry->cacheSeconds();
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isStaleCacheUsable(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0
            && isset($cached['release'])
            && is_array($cached['release'])
            && (now()->getTimestamp() - $cachedAt) <= $this->registry->staleSeconds();
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function githubClient(array $source): PendingRequest
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

        $token = $source['token'] ?? null;

        if (filled($token)) {
            $request = $request->withToken((string) $token);
        }

        return $request;
    }

    private function isRateLimited(Response $response): bool
    {
        return in_array($response->status(), [403, 429], true);
    }
}
