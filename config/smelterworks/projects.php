<?php

/*
 * Content catalog. Swap ProjectCatalog for an Eloquent-backed
 * implementation when you are ready for a database.
 */
return [
    [
        'slug' => 'relic-launcher',
        'name' => 'Relic Launcher',
        'summary' => 'A simple desktop launcher for Vintage Story with VS ModDB, multiple game versions, and themes.',
        'description' => 'Relic Launcher installs Vintage Story versions, browses VS ModDB, manages mods, and launches from one shared folder. Open source on GitHub under 0BSD.',
        'kind' => 'tool',
        'status' => 'active',
        'repo_url' => 'https://github.com/SmelterWorks/Relic-Launcher',
        'page_route' => 'relic',
        'mod_db_url' => null,
        'tags' => ['launcher', 'desktop', 'moddb'],
    ],
];
