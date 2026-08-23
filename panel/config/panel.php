<?php

return [
    'mode' => env('PANEL_MODE', 'managed'),
    'name' => env('PANEL_NAME', 'SmelterWorks Panel'),
    'verify_email' => (bool) env('PANEL_VERIFY_EMAIL', false),
    'database' => [
        'validate_on_boot' => (bool) env('PANEL_DB_VALIDATE_ON_BOOT', true),
    ],
    'regions' => [
        ['code' => 'us', 'label' => 'United States'],
        ['code' => 'eu-de', 'label' => 'Europe (Germany)'],
    ],
    'plans' => [
        'friends' => ['ram_gb' => 4, 'storage_gb' => 25, 'price_monthly' => 10, 'price_yearly' => 100],
        'modded' => ['ram_gb' => 8, 'storage_gb' => 50, 'price_monthly' => 15, 'price_yearly' => 150],
        'heavy' => ['ram_gb' => 16, 'storage_gb' => 100, 'price_monthly' => 25, 'price_yearly' => 250],
        'byos' => ['ram_gb' => 0, 'storage_gb' => 0, 'price_monthly' => 5, 'price_yearly' => 50],
    ],
    'provision' => [
        'secret' => env('PANEL_PROVISION_SECRET'),
    ],
    'stripe' => [
        'enabled' => (bool) env('PANEL_STRIPE_ENABLED', false),
        'key' => env('PANEL_STRIPE_KEY'),
        'secret' => env('PANEL_STRIPE_SECRET'),
        'webhook_secret' => env('PANEL_STRIPE_WEBHOOK_SECRET'),
    ],
    'agent' => [
        'listen_host' => env('PANEL_AGENT_LISTEN_HOST', '127.0.0.1'),
        'listen_port' => (int) env('PANEL_AGENT_LISTEN_PORT', 8081),
        'token_ttl_minutes' => (int) env('PANEL_AGENT_TOKEN_TTL', 60),
    ],
    'security' => [
        'login_max_attempts' => (int) env('PANEL_LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => (int) env('PANEL_LOGIN_DECAY_MINUTES', 15),
        'lockout_minutes' => (int) env('PANEL_LOCKOUT_MINUTES', 30),
        'session_idle_minutes' => (int) env('PANEL_SESSION_IDLE_MINUTES', 120),
        'session_absolute_hours' => (int) env('PANEL_SESSION_ABSOLUTE_HOURS', 72),
        'step_up_minutes' => (int) env('PANEL_STEP_UP_MINUTES', 15),
        'min_password_length' => (int) env('PANEL_MIN_PASSWORD_LENGTH', 8),
        'password_require_lowercase' => (bool) env('PANEL_PASSWORD_REQUIRE_LOWERCASE', true),
        'password_require_uppercase' => (bool) env('PANEL_PASSWORD_REQUIRE_UPPERCASE', false),
        'password_require_number' => (bool) env('PANEL_PASSWORD_REQUIRE_NUMBER', true),
        'password_require_symbol' => (bool) env('PANEL_PASSWORD_REQUIRE_SYMBOL', false),
        'password_min_character_classes' => (int) env('PANEL_PASSWORD_MIN_CHARACTER_CLASSES', 2),
        'password_blocklist' => [
            'password',
            'password123',
            'qwerty123456',
            'letmein123',
            'vintagestory',
            'smelterworks',
        ],
        'register_max_attempts' => (int) env('PANEL_REGISTER_MAX_ATTEMPTS', 5),
        'register_decay_minutes' => (int) env('PANEL_REGISTER_DECAY_MINUTES', 15),
    ],
    'migration' => [
        'per_account_daily' => (int) env('PANEL_MIGRATE_DAILY_LIMIT', 3),
        'concurrent_per_account' => (int) env('PANEL_MIGRATE_CONCURRENT', 1),
        'staging_ttl_hours' => (int) env('PANEL_MIGRATE_STAGING_TTL', 6),
    ],
    'backups' => [
        'tiers' => [
            'starter' => ['storage_gb' => 25, 'price_monthly' => 3, 'price_yearly' => 30],
            'standard' => ['storage_gb' => 50, 'price_monthly' => 4, 'price_yearly' => 40],
            'heavy' => ['storage_gb' => 100, 'price_monthly' => 7, 'price_yearly' => 70],
        ],
    ],
    's3' => [
        'endpoint' => env('AWS_ENDPOINT'),
        'bucket' => env('AWS_BUCKET'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'use_path_style' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'altcha' => [
        'enabled' => (bool) env('PANEL_ALTCHA_ENABLED', false),
        'driver' => env('PANEL_ALTCHA_DRIVER', 'standalone'),
        'hmac_key' => env('PANEL_ALTCHA_HMAC_KEY'),
        'challenge_url' => env('PANEL_ALTCHA_CHALLENGE_URL'),
        'spam_filter' => (bool) env('PANEL_ALTCHA_SPAM_FILTER', false),
    ],
];
