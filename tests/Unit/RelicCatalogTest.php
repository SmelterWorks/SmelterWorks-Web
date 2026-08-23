<?php

namespace Tests\Unit;

use App\Support\Relic\RelicCatalog;
use App\Support\Updates\UpdateMirrorService;
use Illuminate\Support\Facades\Cache;
use Tests\Support\FakesProductUpdates;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class RelicCatalogTest extends TestCase
{
    use FakesProductUpdates;
    use FakesRelicReleases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(UpdateMirrorService::class);
        app()->forgetInstance(RelicCatalog::class);
    }

    public function test_for_view_shows_upstream_tag_when_mirror_is_stale(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.2.0'));

        $relic = app(RelicCatalog::class)->forView();

        $this->assertSame('v0.2.0', $relic['stable_tag']);
    }

    public function test_for_download_page_shows_upstream_tag_when_mirror_is_stale(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.2.0'));

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertSame('v0.2.0', $page['stable']['tag']);
    }

    public function test_for_view_includes_stable_tag_when_release_exists(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $relic = app(RelicCatalog::class)->forView();

        $this->assertSame('v0.1.0', $relic['stable_tag']);
    }

    public function test_for_view_leaves_stable_tag_null_without_release(): void
    {
        $this->fakeRelicEmptyMirror();

        $relic = app(RelicCatalog::class)->forView();

        $this->assertNull($relic['stable_tag']);
    }

    public function test_stable_downloads_use_mirrored_file_urls_when_available(): void
    {
        $this->fakeAndWarmRelicMirror($this->relicStableReleaseFixture('v0.1.0'));

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['stable']['available']);
        $this->assertSame('v0.1.0', $page['stable']['tag']);

        $windows = collect($page['downloads'])->firstWhere('id', 'windows');
        $this->assertTrue($windows['available']);
        $this->assertSame('installer', $windows['default_format']);
        $this->assertSame(
            url('/files/relic/0.1.0/relic-launcher-v0.1.0-win-x64.zip'),
            $windows['url'],
        );

        $installer = collect($windows['formats'])->firstWhere('id', 'installer');
        $this->assertTrue($installer['available']);
        $portable = collect($windows['formats'])->firstWhere('id', 'portable');
        $this->assertTrue($portable['available']);

        $linux = collect($page['downloads'])->firstWhere('id', 'linux');
        $this->assertTrue($linux['available']);
        $this->assertSame('appimage', $linux['default_format']);
        $this->assertTrue(collect($linux['formats'])->firstWhere('id', 'appimage')['available']);
        $this->assertFalse(collect($linux['formats'])->firstWhere('id', 'deb')['available']);
    }

    public function test_stable_downloads_empty_when_no_release_exists(): void
    {
        $this->fakeRelicEmptyMirror();

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertFalse($page['stable']['available']);

        foreach ($page['downloads'] as $download) {
            $this->assertFalse($download['available']);
            $this->assertSame('', $download['url']);
            $this->assertSame('stable', $download['channel']);
        }
    }

    public function test_download_page_resolves_nightly_assets(): void
    {
        $stable = $this->relicStableReleaseFixture('v0.1.0');
        $nightly = [
            'tag_name' => 'nightly-20260804',
            'prerelease' => true,
            'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/nightly-20260804',
            'published_at' => '2026-08-04T05:30:00Z',
            'assets' => [
                [
                    'name' => 'relic-launcher-nightly-20260804-win-x64.zip',
                    'browser_download_url' => 'https://example.test/nightly-win.zip',
                ],
            ],
        ];

        $this->fakeAndWarmRelicMirror($stable, [$nightly]);

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['nightly']['enabled']);
        $this->assertTrue($page['nightly']['available']);
        $this->assertSame('nightly-20260804', $page['nightly']['tag']);

        $windows = collect($page['nightly']['downloads'])->firstWhere('id', 'windows');
        $this->assertSame(
            url('/files/relic/nightly-20260804/relic-launcher-nightly-20260804-win-x64.zip'),
            $windows['url'],
        );
    }
}
