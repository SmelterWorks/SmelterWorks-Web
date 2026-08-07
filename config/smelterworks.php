<?php

return [

    'tagline' => 'Open-source software, mods, and hosting for Vintage Story',

    'mission' => 'SmelterWorks builds open-source tools and mods for Vintage Story. The code is public. Community contributions are welcome.',

    'links' => [
        // Set invite and deploy URLs in .env. Do not commit real invites or secrets.
        'fluxer' => env('SMELTERWORKS_FLUXER_URL'),
        'forgejo' => env('SMELTERWORKS_FORGEJO_URL', 'https://git.smelterworks.com/smelter'),
        'github' => env('SMELTERWORKS_GITHUB_URL', 'https://github.com/SmelterWorks'),
        'relic_repo' => env('SMELTERWORKS_RELIC_REPO_URL', 'https://git.smelterworks.com/smelter/Relic-Launcher'),
        'wiki' => env('SMELTERWORKS_WIKI_URL'),
        'vintage_story' => 'https://www.vintagestory.at/',
        'moddb' => 'https://mods.vintagestory.at/',
        'panel' => env('SMELTERWORKS_PANEL_URL'),
    ],

    'contact' => [
        'email' => env('SMELTERWORKS_CONTACT_EMAIL', 'team@smelterworks.com'),
        'intro' => 'Questions about mods, Relic Launcher, contributions, or support.',
    ],

    'about' => [
        'founder' => 'Ivan (Sudo-Ivan)',
        'founder_note' => 'SmelterWorks was created by Ivan (Sudo-Ivan) for the Vintage Story community.',
    ],

    'donate' => [
        'kofi_url' => env('SMELTERWORKS_KOFI_URL', 'https://ko-fi.com/smelterworks'),
        'intro' => 'Help cover server hardware and keep SmelterWorks projects online.',
    ],

    'nav' => [
        ['label' => 'Hosting', 'route' => 'hosting'],
        ['label' => 'Relic', 'route' => 'relic'],
        ['label' => 'Mods', 'route' => 'mods'],
        ['label' => 'Projects', 'route' => 'projects.index'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Donate', 'route' => 'donate'],
    ],

    'banner' => [
        'enabled' => filter_var(env('SMELTERWORKS_BANNER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'message' => env('SMELTERWORKS_BANNER_MESSAGE', 'Website is under construction'),
        'background' => env('SMELTERWORKS_BANNER_BACKGROUND', '#b45309'),
        'color' => env('SMELTERWORKS_BANNER_COLOR', '#ffffff'),
        'href' => env('SMELTERWORKS_BANNER_HREF'),
        'link_label' => env('SMELTERWORKS_BANNER_LINK_LABEL'),
    ],

    'seo' => [
        'image' => 'images/brand/SmelterWorks-512.png',
        'twitter' => env('SMELTERWORKS_TWITTER'),
    ],

    'legal' => [
        'operator' => 'SmelterWorks',
        'contact_email' => env('SMELTERWORKS_CONTACT_EMAIL'),
        'contact' => env('SMELTERWORKS_LEGAL_CONTACT') ?: env('SMELTERWORKS_FLUXER_URL'),
        'effective_date' => '2026-08-04',
    ],

    'hosting' => require __DIR__.'/smelterworks/hosting.php',
    'relic' => require __DIR__.'/smelterworks/relic.php',
    'projects' => require __DIR__.'/smelterworks/projects.php',
    'branding' => require __DIR__.'/smelterworks/branding.php',
    'servers' => require __DIR__.'/smelterworks/servers.php',
    'updates' => require __DIR__.'/smelterworks/updates.php',

];
