<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

trait FakesRelicReleases
{
    /**
     * @param  array<string, mixed>  $release
     */
    protected function fakeRelicLatestStable(array $release): void
    {
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

    /**
     * @return array<string, mixed>
     */
    protected function relicStableReleaseFixture(string $tag = 'v0.1.0'): array
    {
        $version = ltrim($tag, 'v');

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
            ],
        ];
    }
}
