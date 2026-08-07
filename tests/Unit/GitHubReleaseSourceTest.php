<?php

namespace Tests\Unit;

use App\Support\Updates\Sources\GitHubReleaseSource;
use App\Support\Updates\Sources\RepoUrlParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesRelicReleases;
use Tests\TestCase;

class GitHubReleaseSourceTest extends TestCase
{
    use FakesRelicReleases;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->forgetInstance(GitHubReleaseSource::class);
    }

    public function test_parse_repo_extracts_owner_and_name(): void
    {
        $parsed = app(RepoUrlParser::class)->parse(
            'https://github.com/SmelterWorks/Relic-Launcher',
        );

        $this->assertSame([
            'owner' => 'SmelterWorks',
            'repo' => 'Relic-Launcher',
        ], $parsed);
    }

    public function test_fetch_stable_channel_from_github(): void
    {
        $this->fakeRelicLatestStable($this->relicStableReleaseFixture('v0.1.0'));

        $release = app(GitHubReleaseSource::class)->fetchChannel('relic', 'stable');

        $this->assertNotNull($release);
        $this->assertSame('v0.1.0', $release->tag);
        $this->assertSame('0.1.0', $release->version);
        $this->assertCount(2, $release->assets);
    }

    public function test_fetch_nightly_channel_picks_newest_tag(): void
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
                                'browser_download_url' => 'https://example.test/win-old.zip',
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
                        ],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $release = app(GitHubReleaseSource::class)->fetchChannel('relic', 'nightly');

        $this->assertNotNull($release);
        $this->assertSame('nightly-20260803', $release->tag);
        $this->assertSame('https://example.test/win-newer.zip', $release->assets[0]->downloadUrl);
    }
}
