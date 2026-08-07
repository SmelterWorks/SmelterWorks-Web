<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServerListFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('local');

        config([
            'smelterworks.servers.base_url' => 'https://masterserver.example.test',
            'smelterworks.servers.list_path' => '/api/v1/servers/list',
        ]);
    }

    public function test_servers_page_renders(): void
    {
        Http::fake([
            'masterserver.example.test/*' => Http::response([
                'status' => 'ok',
                'data' => [],
            ], 200),
        ]);

        $this->get(route('servers'))
            ->assertOk()
            ->assertSee('Servers', false)
            ->assertSee('data-servers-page', false)
            ->assertSee(url('/api/v1/servers/list'), false);
    }

    public function test_api_returns_upstream_shape_with_cache_headers(): void
    {
        Http::fake([
            'masterserver.example.test/*' => Http::response([
                'status' => 'ok',
                'data' => [
                    [
                        'serverName' => 'API Server',
                        'serverIP' => 'api.example',
                        'players' => 9,
                    ],
                ],
            ], 200),
        ]);

        $this->get(route('api.servers.list'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.0.serverName', 'API Server')
            ->assertJsonPath('meta.server_count', 1)
            ->assertJsonPath('meta.total_players', 9)
            ->assertHeader('X-Cache')
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('Cache-Control');
    }

    public function test_api_honors_if_none_match_with_etag(): void
    {
        $body = json_encode([
            'status' => 'ok',
            'data' => [
                [
                    'serverName' => 'ETag Server',
                    'serverIP' => 'etag.example',
                    'players' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Storage::disk('local')->put('server-list/last-good.json.gz', gzencode($body, 6));

        Cache::put('servers.master_list.meta', [
            'cached_at' => now()->getTimestamp(),
            'fetched_at' => now()->getTimestamp(),
            'server_count' => 1,
            'etag' => '"etag-value"',
            'source' => 'upstream',
        ], now()->addHour());

        Http::fake();

        $this->withHeaders(['If-None-Match' => '"etag-value"'])
            ->get(route('api.servers.list'))
            ->assertStatus(304)
            ->assertHeader('ETag', '"etag-value"');
    }

    public function test_sitemap_omits_hidden_servers_page(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertDontSee(route('servers'), false);
    }
}
