<?php

namespace Tests\Unit;

use App\Support\Relic\RelicCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelicCatalogTest extends TestCase
{
    public function test_for_view_includes_stable_tag_when_release_exists(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v0.1.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v0.1.0',
                    'published_at' => '2026-08-01T00:00:00Z',
                    'assets' => [],
                ],
            ], 200),
        ]);

        $relic = app(RelicCatalog::class)->forView();

        $this->assertSame('v0.1.0', $relic['stable_tag']);
    }

    public function test_for_view_leaves_stable_tag_null_without_release(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([], 200),
        ]);

        $relic = app(RelicCatalog::class)->forView();

        $this->assertNull($relic['stable_tag']);
    }

    public function test_stable_downloads_use_github_release_assets_when_available(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v1.0.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v1.0.0',
                    'published_at' => '2026-08-01T00:00:00Z',
                    'assets' => [
                        [
                            'name' => 'relic-launcher-v1.0.0-win-x64.zip',
                            'browser_download_url' => 'https://example.test/stable-win.zip',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['stable']['available']);
        $this->assertSame('v1.0.0', $page['stable']['tag']);

        $windows = collect($page['downloads'])->firstWhere('id', 'windows');
        $this->assertTrue($windows['available']);
        $this->assertSame('https://example.test/stable-win.zip', $windows['url']);
    }

    public function test_stable_downloads_empty_when_no_release_exists(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([], 200),
        ]);

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
        Http::fake([
            'api.frankfurter.app/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => '2026-08-04',
                'rates' => ['EUR' => 0.86843],
            ], 200),
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v1.0.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v1.0.0',
                    'published_at' => '2026-08-01T00:00:00Z',
                    'assets' => [],
                ],
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
            ], 200),
        ]);

        $page = app(RelicCatalog::class)->forDownloadPage();

        $this->assertTrue($page['nightly']['enabled']);
        $this->assertTrue($page['nightly']['available']);
        $this->assertSame('nightly-20260804', $page['nightly']['tag']);

        $windows = collect($page['nightly']['downloads'])->firstWhere('id', 'windows');
        $this->assertSame('https://example.test/nightly-win.zip', $windows['url']);
    }
}
