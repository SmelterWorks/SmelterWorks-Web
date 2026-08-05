<?php

namespace Tests\Unit;

use App\Support\Relic\RelicGitHubReleases;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelicGitHubReleasesTest extends TestCase
{
    public function test_parse_repo_extracts_owner_and_name(): void
    {
        $parsed = app(RelicGitHubReleases::class)->parseRepo(
            'https://github.com/SmelterWorks/Relic-Launcher',
        );

        $this->assertSame([
            'owner' => 'SmelterWorks',
            'repo' => 'Relic-Launcher',
        ], $parsed);
    }

    public function test_latest_nightly_picks_newest_nightly_prerelease(): void
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
                    'assets' => [],
                ],
                [
                    'tag_name' => 'nightly-20260801',
                    'prerelease' => true,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/nightly-20260801',
                    'published_at' => '2026-08-01T05:30:00Z',
                    'assets' => [
                        [
                            'name' => 'relic-launcher-nightly-20260801-win-x64.zip',
                            'browser_download_url' => 'https://example.test/win.zip',
                        ],
                    ],
                ],
                [
                    'tag_name' => 'nightly-20260803',
                    'prerelease' => true,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/nightly-20260803',
                    'published_at' => '2026-08-03T05:30:00Z',
                    'assets' => [
                        [
                            'name' => 'relic-launcher-nightly-20260803-win-x64.zip',
                            'browser_download_url' => 'https://example.test/win-newer.zip',
                        ],
                        [
                            'name' => 'relic-launcher-0.0.0-nightly.20260803-x86_64.AppImage',
                            'browser_download_url' => 'https://example.test/linux.AppImage',
                        ],
                        [
                            'name' => 'relic-launcher-nightly-20260803-osx-arm64.app.zip',
                            'browser_download_url' => 'https://example.test/osx-arm.app.zip',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $nightly = app(RelicGitHubReleases::class)->latestNightly('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($nightly);
        $this->assertSame('nightly-20260803', $nightly['tag']);
        $this->assertSame(
            'https://example.test/win-newer.zip',
            app(RelicGitHubReleases::class)->assetUrlForRid($nightly['assets'], 'win-x64'),
        );
        $this->assertSame(
            'https://example.test/linux.AppImage',
            app(RelicGitHubReleases::class)->assetUrlForRid($nightly['assets'], 'linux-x64'),
        );
        $this->assertSame(
            'https://example.test/osx-arm.app.zip',
            app(RelicGitHubReleases::class)->assetUrlForRid($nightly['assets'], 'osx-arm64'),
        );
    }
}
