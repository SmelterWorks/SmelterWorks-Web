<?php

namespace Tests\Unit;

use App\Support\Relic\RelicGitHubReleases;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class RelicGitHubReleasesTest extends TestCase
{
    use FakesRelicReleases;

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
                ], 200);
            }

            return Http::response([], 404);
        });

        $nightly = app(RelicGitHubReleases::class)->latestNightly('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($nightly);
        $this->assertSame('nightly-20260803', $nightly['tag']);
        $this->assertSame(
            'https://example.test/win-newer.zip',
            app(RelicGitHubReleases::class)->assetUrlForRid($nightly['assets'], 'win-x64'),
        );
    }

    public function test_latest_stable_uses_latest_endpoint_not_newer_list_entry(): void
    {
        $latest = $this->relicStableReleaseFixture('v0.1.0');

        Http::fake(function ($request) use ($latest) {
            $url = $request->url();

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response($latest, 200);
            }

            if (str_contains($url, '/releases')) {
                return Http::response([
                    [
                        'tag_name' => 'v1.0.0',
                        'prerelease' => false,
                        'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v1.0.0',
                        'published_at' => '2026-09-01T00:00:00Z',
                        'assets' => [],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v0.1.0', $stable['tag']);
        $this->assertSame(
            'https://example.test/stable-win.zip',
            app(RelicGitHubReleases::class)->assetUrlForRid($stable['assets'], 'win-x64'),
        );
    }

    public function test_stable_and_nightly_use_separate_github_endpoints(): void
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
                        'tag_name' => 'nightly-20260803',
                        'prerelease' => true,
                        'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/nightly-20260803',
                        'published_at' => '2026-08-03T05:30:00Z',
                        'assets' => [],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $service = app(RelicGitHubReleases::class);

        $service->latestStable('SmelterWorks', 'Relic-Launcher');
        $service->latestNightly('SmelterWorks', 'Relic-Launcher');

        Http::assertSentCount(2);
    }

    public function test_github_rate_limit_falls_back_to_forgejo_latest_release(): void
    {
        $release = $this->relicStableReleaseFixture('v0.1.0');

        Http::fake(function ($request) use ($release) {
            $url = $request->url();

            if (str_contains($url, 'api.github.com')) {
                return Http::response(['message' => 'API rate limit exceeded'], 403);
            }

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response($release, 200);
            }

            if (str_contains($url, '/releases')) {
                return Http::response([], 200);
            }

            return Http::response([], 404);
        });

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v0.1.0', $stable['tag']);
    }

    public function test_rate_limited_github_serves_stale_stable_cache(): void
    {
        Cache::put('relic.releases.latest.SmelterWorks.Relic-Launcher', [
            'release' => [
                'tag' => 'v0.1.0',
                'html_url' => 'https://github.com/SmelterWorks/Relic-Launcher/releases/tag/v0.1.0',
                'published_at' => '2026-08-01T00:00:00Z',
                'assets' => [],
            ],
            'cached_at' => now()->subHours(2)->getTimestamp(),
        ], now()->addDay());

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'api.github.com')) {
                return Http::response(['message' => 'API rate limit exceeded'], 403);
            }

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response([], 503);
            }

            return Http::response([], 503);
        });

        $stable = app(RelicGitHubReleases::class)->latestStable('SmelterWorks', 'Relic-Launcher');

        $this->assertNotNull($stable);
        $this->assertSame('v0.1.0', $stable['tag']);
        Http::assertSentCount(2);
    }
}
