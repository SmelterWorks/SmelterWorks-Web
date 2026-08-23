<?php

return [
    'enabled' => (bool) env('METRICS_ENABLED', false),

    'route' => env('METRICS_ROUTE', '/metrics'),

    'token' => env('METRICS_TOKEN'),

    'namespace' => env('METRICS_NAMESPACE', 'smelterworks_panel'),

    'storage' => env('METRICS_STORAGE', 'memory'),

    'redis' => [
        'connection' => env('METRICS_REDIS_CONNECTION', 'default'),
    ],
];
