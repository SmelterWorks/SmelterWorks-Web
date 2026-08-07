<?php

namespace App\Support\Servers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SmelterWorks\VintageStoryServerList\ServerListClient;
use SmelterWorks\VintageStoryServerList\ServerListEndpoints;
use SmelterWorks\VintageStoryServerList\ServerListQuery;
use SmelterWorks\VintageStoryServerList\ServerListSnapshot;

final class MasterServerListService
{
    private const CACHE_KEY = 'servers.master_list.meta';

    private const LOCK_KEY = 'servers.master_list.refresh';

    public function listUrl(): string
    {
        $baseUrl = (string) config('smelterworks.servers.base_url', ServerListEndpoints::DEFAULT_BASE_URL);
        $listPath = (string) config('smelterworks.servers.list_path', ServerListEndpoints::LIST_PATH);

        return ServerListEndpoints::listUrl($baseUrl, $listPath);
    }

    /**
     * @return array{
     *     status: string,
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         cache: string,
     *         fetched_at: int|null,
     *         server_count: int,
     *         etag: string|null
     *     }
     * }
     */
    public function payload(): array
    {
        $meta = $this->normalizeMeta(Cache::get(self::CACHE_KEY));
        $diskBody = $this->readDiskBody();

        if ($meta !== null && $this->isFresh($meta) && $diskBody !== null) {
            return $this->buildPayload($diskBody, $meta, 'HIT');
        }

        if ($meta !== null && $this->isStaleUsable($meta) && $diskBody !== null) {
            return $this->buildPayload($diskBody, $meta, 'STALE');
        }

        if ($diskBody !== null) {
            return $this->buildPayload($diskBody, $meta, 'DISK');
        }

        try {
            $snapshot = $this->fetchAndStore();

            return $this->buildPayload($snapshot->toJson(), $this->normalizeMeta(Cache::get(self::CACHE_KEY)), 'MISS');
        } catch (\Throwable $exception) {
            Log::warning('Master server list unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'data' => [],
                'meta' => [
                    'cache' => 'MISS',
                    'fetched_at' => null,
                    'server_count' => 0,
                    'etag' => null,
                ],
            ];
        }
    }

    public function warmCache(): void
    {
        $meta = $this->normalizeMeta(Cache::get(self::CACHE_KEY));

        if ($meta !== null && $this->isFresh($meta)) {
            return;
        }

        $lock = Cache::lock(self::LOCK_KEY, 60);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->fetchAndStore();
        } catch (\Throwable $exception) {
            Log::warning('Scheduled master server list refresh failed.', [
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }

    private function fetchAndStore(): ServerListSnapshot
    {
        $client = new ServerListClient(
            fetcher: fn (string $url): string => $this->fetchBody($url),
            maxBytes: max(1_048_576, (int) config('smelterworks.servers.max_bytes', 8_388_608)),
        );

        $snapshot = $client->fetch($this->listUrl());
        $json = $snapshot->toJson();

        $this->writeDiskBody($json);
        $summary = ServerListQuery::summary($snapshot->servers);
        $this->rememberMeta([
            'cached_at' => now()->getTimestamp(),
            'fetched_at' => $snapshot->fetchedAt,
            'server_count' => $snapshot->count(),
            'total_players' => $summary['total_players'],
            'latest_major_version' => $summary['latest_major_version'],
            'latest_major_share' => $summary['latest_major_share'],
            'etag' => '"'.($snapshot->contentHash ?? hash('sha256', $json)).'"',
            'source' => 'upstream',
        ]);

        return $snapshot;
    }

    private function fetchBody(string $url): string
    {
        $response = Http::timeout((int) config('smelterworks.servers.http_timeout', 15))
            ->connectTimeout(3)
            ->retry(1, 150, throw: false)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => (string) config('smelterworks.servers.user_agent', 'SmelterWorks-Web'),
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Master server list request failed with HTTP '.$response->status());
        }

        $body = $response->body();

        if ($body === '') {
            throw new RuntimeException('Master server list response was empty.');
        }

        return $body;
    }

    /**
     * @param  array{
     *     cached_at: int,
     *     fetched_at?: int|null,
     *     server_count?: int,
     *     etag?: string|null,
     *     source?: string
     * }  $meta
     * @return array{
     *     status: string,
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         cache: string,
     *         fetched_at: int|null,
     *         server_count: int,
     *         etag: string|null
     *     }
     * }
     */
    private function buildPayload(string $json, ?array $meta, string $cacheState): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Cached server list body was not valid JSON.');
        }

        $data = [];

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            foreach ($decoded['data'] as $entry) {
                if (is_array($entry)) {
                    $data[] = $entry;
                }
            }
        }

        return [
            'status' => (string) ($decoded['status'] ?? 'ok'),
            'data' => $data,
            'meta' => $this->buildMeta($data, $meta, $cacheState),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  array<string, mixed>|null  $meta
     * @return array{
     *     cache: string,
     *     fetched_at: int|null,
     *     server_count: int,
     *     total_players: int,
     *     latest_major_version: string|null,
     *     latest_major_share: int|null,
     *     etag: string|null
     * }
     */
    private function buildMeta(array $data, ?array $meta, string $cacheState): array
    {
        $summary = ServerListQuery::summary($data);

        return [
            'cache' => $cacheState,
            'fetched_at' => isset($meta['fetched_at']) ? (int) $meta['fetched_at'] : null,
            'server_count' => $summary['server_count'],
            'total_players' => $summary['total_players'],
            'latest_major_version' => $summary['latest_major_version'],
            'latest_major_share' => $summary['latest_major_share'],
            'etag' => isset($meta['etag']) ? (string) $meta['etag'] : null,
        ];
    }

    /**
     * @param  array{
     *     cached_at: int,
     *     fetched_at?: int|null,
     *     server_count?: int,
     *     etag?: string|null,
     *     source?: string
     * }  $payload
     */
    private function rememberMeta(array $payload): void
    {
        Cache::put(self::CACHE_KEY, $payload, now()->addSeconds($this->staleCacheSeconds()));
    }

    /**
     * @return array{
     *     cached_at: int,
     *     fetched_at?: int|null,
     *     server_count?: int,
     *     etag?: string|null,
     *     source?: string
     * }|null
     */
    private function normalizeMeta(mixed $meta): ?array
    {
        if (! is_array($meta) || ! isset($meta['cached_at'])) {
            return null;
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function isFresh(array $meta): bool
    {
        $cachedAt = (int) ($meta['cached_at'] ?? 0);

        return $cachedAt > 0
            && (now()->getTimestamp() - $cachedAt) <= $this->cacheSeconds();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function isStaleUsable(array $meta): bool
    {
        $cachedAt = (int) ($meta['cached_at'] ?? 0);

        return $cachedAt > 0
            && (now()->getTimestamp() - $cachedAt) <= $this->staleCacheSeconds();
    }

    private function readDiskBody(): ?string
    {
        $path = (string) config('smelterworks.servers.disk_path', 'server-list/last-good.json.gz');

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $compressed = Storage::disk('local')->get($path);

        if (! is_string($compressed) || $compressed === '') {
            return null;
        }

        $body = gzdecode($compressed);

        return is_string($body) && $body !== '' ? $body : null;
    }

    private function writeDiskBody(string $json): void
    {
        $path = (string) config('smelterworks.servers.disk_path', 'server-list/last-good.json.gz');
        $compressed = gzencode($json, 6);

        if ($compressed === false) {
            throw new RuntimeException('Failed to compress server list payload.');
        }

        Storage::disk('local')->put($path, $compressed);
    }

    private function cacheSeconds(): int
    {
        return max(60, (int) config('smelterworks.servers.cache_seconds', 120));
    }

    private function staleCacheSeconds(): int
    {
        return max($this->cacheSeconds(), (int) config('smelterworks.servers.stale_seconds', 604800));
    }
}
