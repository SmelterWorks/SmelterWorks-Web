<?php

return [
    'name' => 'Relic Launcher',
    'tagline' => 'Unofficial desktop launcher for Vintage Story',
    'summary' => 'Install the game, browse VS ModDB, add mods, and launch from one app. Works on Windows, Linux, and Mac.',
    'repo_url' => env('SMELTERWORKS_RELIC_REPO_URL', 'https://git.smelterworks.com/smelter/Relic-Launcher'),
    // GitHub mirror used for release binaries and nightly API lookups.
    'releases_repo_url' => env('SMELTERWORKS_RELIC_RELEASES_REPO_URL', 'https://github.com/SmelterWorks/Relic-Launcher'),
    // Blank derives {releases_repo_url}/releases/latest when a stable release exists.
    'releases_url' => env('SMELTERWORKS_RELIC_RELEASES_URL'),
    'preview_url' => env('SMELTERWORKS_RELIC_PREVIEW_URL'),
    'preview_assets' => [
        'webp' => 'images/relic/home-relic-default.webp',
        'jpg' => 'images/relic/home-relic-default.jpg',
    ],
    'preview_alt' => 'Relic Launcher home screen with game versions and news',
    'license' => '0BSD',
    'nightly' => [
        'enabled' => filter_var(env('SMELTERWORKS_RELIC_NIGHTLY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'platforms' => [
        ['icon' => 'windows', 'label' => 'Windows', 'detail' => '10+ x64'],
        ['icon' => 'linux', 'label' => 'Linux', 'detail' => 'x64, X11 and Wayland'],
        ['icon' => 'macos', 'label' => 'macOS', 'detail' => '13+ Intel and Apple Silicon'],
    ],
    'downloads' => [
        [
            'id' => 'windows',
            'rid' => 'win-x64',
            'label' => 'Windows',
            'detail' => 'Windows 10+ x64 · zip',
            'match' => ['windows', 'win'],
        ],
        [
            'id' => 'linux',
            'rid' => 'linux-x64',
            'label' => 'Linux',
            'detail' => 'Linux x64 · AppImage, deb, rpm, and Arch packages on Releases',
            'match' => ['linux', 'x11', 'wayland'],
        ],
        [
            'id' => 'macos-arm',
            'rid' => 'osx-arm64',
            'label' => 'macOS (Apple Silicon)',
            'detail' => 'macOS 13+ arm64 · app zip',
            'match' => ['mac-arm', 'macos-arm'],
        ],
        [
            'id' => 'macos-intel',
            'rid' => 'osx-x64',
            'label' => 'macOS (Intel)',
            'detail' => 'macOS 13+ x64 · app zip',
            'match' => ['mac-intel', 'macos-intel'],
        ],
    ],
    'features' => [
        ['icon' => 'log-in', 'text' => 'Sign in with your Vintage Story account'],
        ['icon' => 'layers', 'text' => 'Install multiple game versions'],
        ['icon' => 'store', 'text' => 'Browse VS ModDB and install or manage mods'],
        ['icon' => 'folder-open', 'text' => 'Local mods support'],
        ['icon' => 'shield-alert', 'text' => 'Optional warning when a mod is on the official blocked-mods list'],
        ['icon' => 'circle-play', 'text' => 'One-click play with mods, saves, and worlds in one shared folder'],
        ['icon' => 'newspaper', 'text' => 'Game news and custom themes'],
        ['icon' => 'book-open', 'text' => 'In-app Vintage Story wiki browser'],
    ],
    'upcoming' => [
        ['icon' => 'archive', 'text' => 'Backup mods, worlds, and game versions'],
        ['icon' => 'shield', 'text' => 'Launcher sandboxing (the app itself, not the game)'],
        ['icon' => 'box', 'text' => 'Flatpak packaging'],
        ['icon' => 'type', 'text' => 'Custom fonts support'],
        ['icon' => 'key-round', 'text' => 'Avoid logging in twice (launcher then game)'],
    ],
];
