<?php

namespace Tests\Support;

use App\Support\Updates\Sources\GitHubReleaseSource;
use App\Support\Updates\Sources\UpdateSourceResolver;
use App\Support\Updates\UpdateMirrorService;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

trait FakesRelicReleases
{
    /**
     * @param  array<string, mixed>  $release
     */
    protected function fakeRelicLatestStable(array $release): void
    {
        app()->forgetInstance(GitHubReleaseSource::class);
        app()->forgetInstance(UpdateSourceResolver::class);
        app()->forgetInstance(UpdateMirrorService::class);

        $this->resetHttpFakes();

        Http::fake(function ($request) use ($release) {
            $url = $request->url();

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response($release, 200);
            }

            if (str_contains($url, '/releases')) {
                return Http::response([$release], 200);
            }

            return Http::response([], 404);
        });
    }

    protected function fakeRelicEmptyReleases(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response([], 404);
            }

            if (str_contains($url, '/releases')) {
                return Http::response([], 200);
            }

            return Http::response([], 404);
        });
    }

    protected function resetHttpFakes(): void
    {
        Http::swap(new Factory(app('events')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function relicStableReleaseFixture(string $tag = 'v0.1.0'): array
    {
        return [
            'tag_name' => $tag,
            'prerelease' => false,
            'html_url' => "https://github.com/SmelterWorks/Relic-Launcher/releases/tag/{$tag}",
            'published_at' => '2026-08-01T00:00:00Z',
            'assets' => [
                [
                    'name' => "relic-launcher-{$tag}-win-x64.zip",
                    'browser_download_url' => 'https://example.test/stable-win.zip',
                ],
                [
                    'name' => "relic-launcher-{$tag}-linux-x64.AppImage",
                    'browser_download_url' => 'https://example.test/stable-linux.AppImage',
                ],
            ],
        ];
    }
}
