<?php

namespace Tests\Unit;

use App\Support\Servers\MasterServerListService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterServerListServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('local');

        config([
            'smelterworks.servers.base_url' => 'https://masterserver.example.test',
            'smelterworks.servers.list_path' => '/api/v1/servers/list',
            'smelterworks.servers.cache_seconds' => 120,
            'smelterworks.servers.stale_seconds' => 604800,
        ]);
    }

    public function test_it_fetches_and_writes_disk_cache(): void
    {
        Http::fake([
            'masterserver.example.test/*' => Http::response([
                'status' => 'ok',
                'data' => [
                    [
                        'serverName' => 'Cached Server',
                        'serverIP' => 'cached.example',
                        'players' => 2,
                    ],
                ],
            ], 200),
        ]);

        $service = app(MasterServerListService::class);
        $payload = $service->payload();

        $this->assertSame('ok', $payload['status']);
        $this->assertCount(1, $payload['data']);
        $this->assertSame('MISS', $payload['meta']['cache']);
        $this->assertTrue(Storage::disk('local')->exists('server-list/last-good.json.gz'));

        $service->payload();
        Http::assertSentCount(1);
    }

    public function test_fresh_cache_serves_disk_without_network(): void
    {
        $body = json_encode([
            'status' => 'ok',
            'data' => [
                [
                    'serverName' => 'Fresh Cache',
                    'serverIP' => 'fresh.example',
                    'players' => 5,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Storage::disk('local')->put('server-list/last-good.json.gz', gzencode($body, 6));

        Cache::put('servers.master_list.meta', [
            'cached_at' => now()->getTimestamp(),
            'fetched_at' => now()->getTimestamp(),
            'server_count' => 1,
            'etag' => '"abc123"',
            'source' => 'upstream',
        ], now()->addHour());

        Http::fake();

        $payload = app(MasterServerListService::class)->payload();

        $this->assertSame('HIT', $payload['meta']['cache']);
        $this->assertSame('Fresh Cache', $payload['data'][0]['serverName']);
        Http::assertNothingSent();
    }

    public function test_stale_cache_serves_disk_when_upstream_fails(): void
    {
        $body = json_encode([
            'status' => 'ok',
            'data' => [
                [
                    'serverName' => 'Stale Cache',
                    'serverIP' => 'stale.example',
                    'players' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Storage::disk('local')->put('server-list/last-good.json.gz', gzencode($body, 6));

        Cache::put('servers.master_list.meta', [
            'cached_at' => now()->subMinutes(5)->getTimestamp(),
            'fetched_at' => now()->subMinutes(5)->getTimestamp(),
            'server_count' => 1,
            'etag' => '"stale123"',
            'source' => 'upstream',
        ], now()->addDay());

        Http::fake([
            'masterserver.example.test/*' => Http::response('nope', 503),
        ]);

        $payload = app(MasterServerListService::class)->payload();

        $this->assertSame('STALE', $payload['meta']['cache']);
        $this->assertSame('Stale Cache', $payload['data'][0]['serverName']);
        Http::assertNothingSent();
    }

    public function test_disk_fallback_is_used_when_metadata_is_missing(): void
    {
        $body = json_encode([
            'status' => 'ok',
            'data' => [
                [
                    'serverName' => 'Disk Only',
                    'serverIP' => 'disk.example',
                    'players' => 7,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Storage::disk('local')->put('server-list/last-good.json.gz', gzencode($body, 6));

        Http::fake([
            'masterserver.example.test/*' => Http::response('nope', 503),
        ]);

        $payload = app(MasterServerListService::class)->payload();

        $this->assertSame('DISK', $payload['meta']['cache']);
        $this->assertSame('Disk Only', $payload['data'][0]['serverName']);
    }

    public function test_cold_start_without_disk_returns_error_payload(): void
    {
        Http::fake([
            'masterserver.example.test/*' => Http::response('nope', 503),
        ]);

        $payload = app(MasterServerListService::class)->payload();

        $this->assertSame('error', $payload['status']);
        $this->assertSame([], $payload['data']);
        $this->assertSame('MISS', $payload['meta']['cache']);
    }
}
