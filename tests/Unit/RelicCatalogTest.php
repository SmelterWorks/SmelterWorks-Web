<?php

namespace Tests\Unit;

use App\Support\Relic\RelicCatalog;
use App\Support\Relic\RelicGitHubReleases;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class RelicCatalogTest extends TestCase
{
    use FakesRelicReleases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(RelicGitHubReleases::class);
        app()->forgetInstance(RelicCatalog::class);
    }

    public function test_for_view_includes_stable_tag_when_release_exists(): void
    {
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.1.0'));

        $relic = app(RelicCatalog::class)->forView();

        $this->assertSame('v0.1.0', $relic['stable_tag']);
    }

    public function test_for_view_leaves_stable_tag_null_without_release(): void
    {
        $this->fakeRelicEmptyReleases();

        $relic = app(RelicCatalog::class)->forView();

        $this->assertNull($relic['stable_tag']);
    }

    public function test_stable_downloads_use_github_release_assets_when_available(): void
    {
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.1.0'));

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['stable']['available']);
        $this->assertSame('v0.1.0', $page['stable']['tag']);

        $windows = collect($page['downloads'])->firstWhere('id', 'windows');
        $this->assertTrue($windows['available']);
        $this->assertSame('https://example.test/stable-win.zip', $windows['url']);
    }

    public function test_stable_downloads_empty_when_no_release_exists(): void
    {
        $this->fakeRelicEmptyReleases();

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

        Http::fake(function ($request) use ($stable) {
            $url = $request->url();

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response($stable, 200);
            }

            if (str_contains($url, '/releases')) {
                return Http::response([
                    $stable,
                    [
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
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['nightly']['enabled']);
        $this->assertTrue($page['nightly']['available']);
        $this->assertSame('nightly-20260804', $page['nightly']['tag']);

        $windows = collect($page['nightly']['downloads'])->firstWhere('id', 'windows');
        $this->assertSame('https://example.test/nightly-win.zip', $windows['url']);
    }
}
