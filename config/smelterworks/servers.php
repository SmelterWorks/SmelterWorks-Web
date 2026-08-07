<?php

use SmelterWorks\VintageStoryServerList\ServerListEndpoints;

return [

    'base_url' => env(
        'SMELTERWORKS_SERVERS_BASE_URL',
        ServerListEndpoints::DEFAULT_BASE_URL,
    ),

    'list_path' => env(
        'SMELTERWORKS_SERVERS_LIST_PATH',
        ServerListEndpoints::LIST_PATH,
    ),

    'cache_seconds' => max(60, (int) env('SMELTERWORKS_SERVERS_CACHE_SECONDS', 120)),

    'stale_seconds' => max(
        max(60, (int) env('SMELTERWORKS_SERVERS_CACHE_SECONDS', 120)),
        (int) env('SMELTERWORKS_SERVERS_STALE_SECONDS', 604800),
    ),

    'http_timeout' => max(3, (int) env('SMELTERWORKS_SERVERS_HTTP_TIMEOUT', 15)),

    'max_bytes' => max(1_048_576, (int) env('SMELTERWORKS_SERVERS_MAX_BYTES', 8_388_608)),

    'user_agent' => env('SMELTERWORKS_SERVERS_USER_AGENT', 'SmelterWorks-Web'),

    'disk_path' => 'server-list/last-good.json.gz',

    'official_list_url' => env(
        'SMELTERWORKS_SERVERS_OFFICIAL_URL',
        'https://servers.vintagestory.at/',
    ),

];
