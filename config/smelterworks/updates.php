<?php

/**
 * Product update mirror registry.
 *
 * Launcher contract (v1): GET /updates/{product}/{channel}.json
 * - schemaVersion: 1
 * - product, channel, version, publishedAt, releaseNotesUrl
 * - assets[]: installKind, rid, filename, url, sha256, sizeBytes
 *
 * Binary delivery: GET /files/{product}/{version}/{filename}
 * Public asset urls never point at upstream hosts.
 */
return [
    'public_base_url' => env('SMELTERWORKS_UPDATES_PUBLIC_BASE_URL'),
    'disk' => env('SMELTERWORKS_UPDATES_DISK', 'local'),
    'max_asset_bytes' => (int) env('SMELTERWORKS_UPDATES_MAX_ASSET_BYTES', 524_288_000),
    'warm_lock_seconds' => (int) env('SMELTERWORKS_UPDATES_WARM_LOCK_SECONDS', 600),
    'warm_interval_minutes' => (int) env('SMELTERWORKS_UPDATES_WARM_INTERVAL_MINUTES', 30),
    'cache_seconds' => (int) env('SMELTERWORKS_UPDATES_CACHE_SECONDS', 3600),
    'stale_seconds' => (int) env('SMELTERWORKS_UPDATES_STALE_SECONDS', 86400),
    'use_accel_redirect' => filter_var(
        env('SMELTERWORKS_UPDATES_USE_ACCEL_REDIRECT', true),
        FILTER_VALIDATE_BOOLEAN,
    ),
    'products' => [
        'relic' => [
            'enabled' => true,
            'name' => 'Relic Launcher',
            'channels' => [
                'stable' => [
                    'enabled' => true,
                    'selector' => 'latest_stable',
                ],
                'nightly' => [
                    'enabled' => filter_var(env('SMELTERWORKS_RELIC_NIGHTLY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                    'selector' => 'latest_prerelease_tag',
                    'tag_pattern' => '/^nightly-\d{8}$/',
                ],
            ],
            'source' => [
                'driver' => 'github',
                'repo_url' => env('SMELTERWORKS_RELIC_RELEASES_REPO_URL', 'https://github.com/SmelterWorks/Relic-Launcher'),
                'forgejo_repo_url' => env('SMELTERWORKS_RELIC_FORGEJO_REPO_URL'),
                'token' => env('SMELTERWORKS_GITHUB_TOKEN'),
                'allowed_hosts' => [
                    'github.com',
                    'api.github.com',
                    'objects.githubusercontent.com',
                    'release-assets.githubusercontent.com',
                    'git.smelterworks.com',
                ],
            ],
            'assets' => [
                [
                    'rid' => 'win-x64',
                    'installKind' => 'WindowsInstaller',
                    'match' => ['*.msi', '*-setup.exe', '*Setup.exe'],
                    'prefer' => ['*win-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'win-x64',
                    'installKind' => 'WindowsZip',
                    'match' => ['*.zip'],
                    'prefer' => ['*win-x64*'],
                    'reject' => ['*.app.zip'],
                ],
                [
                    'rid' => 'linux-x64',
                    'installKind' => 'LinuxAppImage',
                    'match' => ['*.AppImage', '*.appimage'],
                    'prefer' => ['*linux-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'linux-x64',
                    'installKind' => 'LinuxDeb',
                    'match' => ['*.deb'],
                    'prefer' => ['*linux-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'linux-x64',
                    'installKind' => 'LinuxRpm',
                    'match' => ['*.rpm'],
                    'prefer' => ['*linux-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'linux-x64',
                    'installKind' => 'LinuxFlatpak',
                    'match' => ['*.flatpak'],
                    'prefer' => ['*linux-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'linux-x64',
                    'installKind' => 'LinuxTarGz',
                    'match' => ['*.tar.gz'],
                    'prefer' => ['*linux-x64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'osx-arm64',
                    'installKind' => 'MacAppZip',
                    'match' => ['*.app.zip'],
                    'prefer' => ['*osx-arm64*', '*arm64*'],
                    'reject' => [],
                ],
                [
                    'rid' => 'osx-x64',
                    'installKind' => 'MacAppZip',
                    'match' => ['*.app.zip'],
                    'prefer' => ['*osx-x64*'],
                    'reject' => [],
                ],
            ],
        ],
    ],
];
