<?php

namespace Tests\Unit;

use SmelterWorks\VintageStoryServerList\ServerListClient;
use SmelterWorks\VintageStoryServerList\ServerListEndpoints;
use SmelterWorks\VintageStoryServerList\ServerListQuery;
use SmelterWorks\VintageStoryServerList\ServerListSnapshot;
use Tests\TestCase;

class ServerListPackageTest extends TestCase
{
    public function test_endpoints_build_list_url_from_base_and_path(): void
    {
        $url = ServerListEndpoints::listUrl(
            'https://masterserver.example.test',
            '/api/v1/servers/list',
        );

        $this->assertSame('https://masterserver.example.test/api/v1/servers/list', $url);
    }

    public function test_client_parses_valid_payload(): void
    {
        $body = json_encode([
            'status' => 'ok',
            'data' => [
                [
                    'serverName' => 'Test Server',
                    'serverIP' => '127.0.0.1',
                    'players' => 3,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new ServerListClient(fetcher: fn (): string => $body);
        $snapshot = $client->fetch('https://masterserver.example.test/api/v1/servers/list');

        $this->assertInstanceOf(ServerListSnapshot::class, $snapshot);
        $this->assertCount(1, $snapshot->servers);
        $this->assertSame(3, $snapshot->totalPlayers());
        $this->assertSame('ok', $snapshot->toResponse()['status']);
    }

    public function test_client_rejects_non_https_urls(): void
    {
        $client = new ServerListClient(fetcher: fn (): string => '{}');

        $this->expectException(\RuntimeException::class);
        $client->fetch('http://masterserver.example.test/api/v1/servers/list');
    }

    public function test_query_filters_search_version_and_empty_servers(): void
    {
        $servers = [
            [
                'serverName' => 'Alpha',
                'serverIP' => 'alpha.example',
                'gameVersion' => '1.22.x',
                'players' => 0,
                'mods' => [],
                'hasPassword' => false,
                'whitelisted' => false,
            ],
            [
                'serverName' => 'Beta RP',
                'serverIP' => 'beta.example',
                'gameVersion' => '1.21.x',
                'players' => 4,
                'mods' => ['foo'],
                'hasPassword' => true,
                'whitelisted' => true,
                'playstyle' => ['id' => 'surviveandbuild'],
            ],
        ];

        $filtered = ServerListQuery::apply($servers, [
            'search' => 'beta',
            'version' => '1.21.x',
            'hideEmpty' => true,
            'hasMods' => true,
            'hasPassword' => true,
            'whitelisted' => true,
            'playstyle' => 'surviveandbuild',
            'sort' => 'name',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame('Beta RP', $filtered[0]['serverName']);
        $this->assertSame(['1.21.x', '1.22.x'], ServerListQuery::versions($servers));
        $this->assertSame(['surviveandbuild'], ServerListQuery::playstyles($servers));
    }

    public function test_latest_major_version_share_uses_highest_version_band(): void
    {
        $servers = [
            ['gameVersion' => '1.20.0', 'players' => 1],
            ['gameVersion' => '1.22.5', 'players' => 3],
            ['gameVersion' => '1.22.6', 'players' => 2],
            ['gameVersion' => '1.21.5', 'players' => 1],
        ];

        $this->assertSame('1.22', ServerListQuery::latestMajorVersion($servers));
        $this->assertSame(50, ServerListQuery::latestMajorVersionShare($servers));
    }
}
