<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RelicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_relic_page_renders_with_default_config(): void
    {
        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('Relic Launcher', false)
            ->assertSee(route('relic.download'), false)
            ->assertSee('Download', false)
            ->assertSee('Install the game, browse VS ModDB', false)
            ->assertSee('platform-chip', false)
            ->assertSee('home-relic-default.png', false)
            ->assertSee('not affiliated with Anego Studios', false)
            ->assertDontSee('Source lives at', false)
            ->assertDontSee('.NET', false)
            ->assertDontSee('Avalonia', false);
    }

    public function test_relic_page_renders_with_legacy_string_platform_config(): void
    {
        Config::set('smelterworks.relic.platforms', [
            'Windows 10+ x64',
            'Linux x64 (X11 and native Wayland)',
            'macOS 13+ (x64 and arm64)',
        ]);

        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('platform-chip', false)
            ->assertSee('Windows', false)
            ->assertSee('Linux', false)
            ->assertSee('macOS', false);
    }

    public function test_relic_download_page_detects_windows(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->get(route('relic.download'))
            ->assertOk()
            ->assertSee('Download Relic Launcher', false)
            ->assertSee('Looks like you are on Windows', false)
            ->assertSee('Suggested for you', false)
            ->assertSee('Download Windows', false)
            ->assertSee('download-card__icon', false)
            ->assertSee('releases/latest', false)
            ->assertSee('Nightly pre-release', false)
            ->assertSee('Pre-release', false);
    }

    public function test_relic_download_page_detects_linux(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36')
            ->get(route('relic.download'))
            ->assertOk()
            ->assertSee('Looks like you are on Linux', false)
            ->assertSee('Download Linux', false);
    }

    public function test_relic_download_page_can_hide_nightly_channel(): void
    {
        Config::set('smelterworks.relic.nightly.enabled', false);

        $this->get(route('relic.download'))
            ->assertOk()
            ->assertSee('releases/latest', false)
            ->assertDontSee('Nightly pre-release', false);
    }

    public function test_relic_pages_do_not_promote_hosting(): void
    {
        $this->get(route('relic'))
            ->assertDontSee('View hosting', false)
            ->assertDontSee('Buy hosting', false)
            ->assertDontSee('Server hosting integration', false);

        $this->get(route('relic.download'))
            ->assertDontSee('View hosting', false)
            ->assertDontSee('Buy hosting', false);
    }
}
