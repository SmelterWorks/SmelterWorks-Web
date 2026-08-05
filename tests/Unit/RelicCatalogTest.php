<?php

namespace Tests\Unit;

use App\Support\Relic\RelicCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelicCatalogTest extends TestCase
{
    public function test_stable_downloads_all_point_at_releases_latest(): void
    {
        $relic = app(RelicCatalog::class)->forView();

        $this->assertSame(
            'https://github.com/SmelterWorks/Relic-Launcher/releases/latest',
            $relic['releases_url'],
        );

        foreach ($relic['downloads'] as $download) {
            $this->assertSame($relic['releases_url'], $download['url']);
            $this->assertSame('stable', $download['channel']);
            $this->assertNotSame('', $download['rid']);
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
