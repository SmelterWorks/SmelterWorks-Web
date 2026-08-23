<?php

/*
 * Content catalog. Swap ProjectCatalog for an Eloquent-backed
 * implementation when you are ready for a database.
 */
return [
    [
        'slug' => 'better-sprinting',
        'name' => 'Better Sprinting',
        'summary' => 'Client-side sprint QoL with Minecraft-style double-tap forward and optional auto-sprint.',
        'description' => 'Double-tap forward to sprint, or enable auto-sprint to keep sprinting while holding forward. Open settings with Ctrl+Shift+R. Client-only mod for Vintage Story 1.22.0 or newer. Open source on GitHub under 0BSD.',
        'kind' => 'mod',
        'status' => 'active',
        'repo_url' => 'https://github.com/SmelterWorks/BetterSprinting',
        'page_route' => null,
        'mod_db_url' => null,
        'tags' => ['client', 'qol', 'movement'],
    ],
    [
        'slug' => 'relic-launcher',
        'name' => 'Relic Launcher',
        'summary' => 'A simple desktop launcher for Vintage Story with VS ModDB, multiple game versions, and themes.',
        'description' => 'Relic Launcher installs Vintage Story versions, browses VS ModDB, manages mods, and launches from one shared folder. Open source on Forgejo under 0BSD.',
        'kind' => 'tool',
        'status' => 'active',
        'repo_url' => 'https://git.smelterworks.com/smelter/Relic-Launcher',
        'page_route' => 'relic',
        'mod_db_url' => null,
        'tags' => ['launcher', 'desktop', 'moddb'],
    ],
];
