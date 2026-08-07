<?php

namespace Tests\Feature;

use App\Support\Relic\RelicCatalog;
use App\Support\Updates\UpdateMirrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\Support\FakesProductUpdates;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class RelicPageTest extends TestCase
{
    use FakesProductUpdates;
    use FakesRelicReleases;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(UpdateMirrorService::class);
        app()->forgetInstance(RelicCatalog::class);
    }

    public function test_relic_page_renders_with_default_config(): void
    {
        $this->fakeRelicEmptyMirror();

        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('Relic Launcher', false)
            ->assertSee(route('relic.download'), false)
            ->assertSee('Download', false)
            ->assertDontSee('button__badge--version', false)
            ->assertSee('Install the game, browse VS ModDB', false)
            ->assertSee('platform-chip', false)
            ->assertSee('home-relic-default.webp', false)
            ->assertSee('not affiliated with Anego Studios', false)
            ->assertDontSee('Source lives at', false)
            ->assertDontSee('.NET', false)
            ->assertDontSee('Avalonia', false);
    }

    public function test_relic_page_shows_stable_version_badge_on_download_button(): void
    {
        $this->fakeStableMirror();

        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('button__badge--version', false)
            ->assertSee('>v0.1.0<', false);
    }

    public function test_relic_page_renders_with_legacy_string_platform_config(): void
    {
        Config::set('smelterworks.relic.platforms', [
            'Windows 10+ x64',
            'Linux x64 (X11 and native Wayland)',
            'macOS 13+ (x64 and arm64)',
        ]);

        $this->fakeRelicEmptyMirror();

        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('platform-chip', false)
            ->assertSee('Windows', false)
            ->assertSee('Linux', false)
            ->assertSee('macOS', false);
    }

    public function test_relic_download_page_detects_windows(): void
    {
        $this->fakeStableMirror();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->get(route('relic.download'))
            ->assertOk()
            ->assertSee('Download Relic Launcher', false)
            ->assertSee('Looks like you are on Windows', false)
            ->assertSee('Suggested for you', false)
            ->assertSee('Download Windows', false)
            ->assertSee('download-formats__option', false)
            ->assertSee('Installer', false)
            ->assertSee('Portable', false)
            ->assertSee('relic-preview--compact', false)
            ->assertSee('home-relic-default.webp', false)
            ->assertSee('download-card__icon', false)
            ->assertSee('v0.1.0', false)
            ->assertSee('Nightly pre-release', false)
            ->assertSee('Pre-release', false);
    }

    public function test_relic_download_page_detects_linux(): void
    {
        $this->fakeStableMirror();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36')
            ->get(route('relic.download'))
            ->assertOk()
            ->assertSee('Looks like you are on Linux', false)
            ->assertSee('Download Linux', false)
            ->assertSee('AppImage', false)
            ->assertSee('Fedora (RPM)', false)
            ->assertSee('Flatpak', false);
    }

    public function test_relic_download_page_shows_empty_state_without_releases(): void
    {
        $this->fakeRelicEmptyMirror();

        $this->get(route('relic.download'))
            ->assertOk()
            ->assertSee('No stable build is mirrored on this site yet', false)
            ->assertSee('No nightly pre-release is published right now', false)
            ->assertSee('Not available', false)
            ->assertSee('No nightly yet', false)
            ->assertDontSee('releases/latest', false);
    }

    public function test_relic_download_page_can_hide_nightly_channel(): void
    {
        $this->fakeStableMirror();
        Config::set('smelterworks.relic.nightly.enabled', false);

        $this->get(route('relic.download'))
            ->assertOk()
            ->assertSee('v0.1.0', false)
            ->assertDontSee('Nightly pre-release', false);
    }

    public function test_relic_pages_do_not_promote_hosting(): void
    {
        $this->fakeRelicEmptyMirror();

        $this->get(route('relic'))
            ->assertDontSee('View hosting', false)
            ->assertDontSee('Buy hosting', false)
            ->assertDontSee('Server hosting integration', false);

        $this->fakeStableMirror();

        $this->get(route('relic.download'))
            ->assertDontSee('View hosting', false)
            ->assertDontSee('Buy hosting', false);
    }

    private function fakeStableMirror(): void
    {
        Cache::flush();
        app()->forgetInstance(UpdateMirrorService::class);
        app()->forgetInstance(RelicCatalog::class);

        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));
    }
}
