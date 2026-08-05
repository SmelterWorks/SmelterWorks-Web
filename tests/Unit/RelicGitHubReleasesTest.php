<?php

namespace Tests\Unit;

use App\Support\Relic\RelicGitHubReleases;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelicGitHubReleasesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(RelicGitHubReleases::class);
    }

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

    public function test_latest_stable_picks_newest_non_prerelease(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v0.9.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v0.9.0',
                    'published_at' => '2026-07-01T00:00:00Z',
                    'assets' => [],
                ],
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

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v1.0.0', $stable['tag']);
        $this->assertSame(
            'https://example.test/stable-win.zip',
            app(RelicGitHubReleases::class)->assetUrlForRid($stable['assets'], 'win-x64'),
        );
    }

    public function test_stable_and_nightly_share_one_github_request(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v1.0.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v1.0.0',
                    'published_at' => '2026-08-01T00:00:00Z',
                    'assets' => [],
                ],
                [
                    'tag_name' => 'nightly-20260803',
                    'prerelease' => true,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/nightly-20260803',
                    'published_at' => '2026-08-03T05:30:00Z',
                    'assets' => [],
                ],
            ], 200),
        ]);

        $service = app(RelicGitHubReleases::class);

        $service->latestStable('SmelterWorks', 'Relic-Launcher');
        $service->latestNightly('SmelterWorks', 'Relic-Launcher');

        Http::assertSentCount(1);
    }

    public function test_github_rate_limit_falls_back_to_forgejo_releases_api(): void
    {
        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                'message' => 'API rate limit exceeded',
            ], 403),
            'git.smelterworks.com/api/v1/repos/smelter/Relic-Launcher/releases*' => Http::response([
                [
                    'tag_name' => 'v0.1.0',
                    'prerelease' => false,
                    'html_url' => 'https://git.smelterworks.com/smelter/Relic-Launcher/releases/tag/v0.1.0',
                    'published_at' => '2026-08-01T00:00:00Z',
                    'assets' => [],
                ],
            ], 200),
        ]);

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v0.1.0', $stable['tag']);
    }

    public function test_rate_limited_github_serves_stale_cache(): void
    {
        Cache::put('relic.releases.SmelterWorks.Relic-Launcher', [
            'releases' => [
                [
                    'tag_name' => 'v0.9.0',
                    'prerelease' => false,
                    'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v0.9.0',
                    'published_at' => '2026-07-01T00:00:00Z',
                    'assets' => [],
                ],
            ],
            'cached_at' => now()->subHours(2)->getTimestamp(),
        ], now()->addDay());

        Http::fake([
            'api.github.com/repos/SmelterWorks/Relic-Launcher/releases*' => Http::response([
                'message' => 'API rate limit exceeded',
            ], 403),
            'git.smelterworks.com/api/v1/repos/smelter/Relic-Launcher/releases*' => Http::response([], 503),
        ]);

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v0.9.0', $stable['tag']);
        Http::assertSentCount(2);
    }
}
