<?php

namespace Tests\Support;

use App\Support\Updates\UpdateMirrorService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait FakesProductUpdates
{
    protected function resetUpdateMirror(): void
    {
        Storage::fake('local');
        Config::set('smelterworks.updates.disk', 'local');
        Config::set('smelterworks.updates.use_accel_redirect', false);
        Config::set('smelterworks.updates.products.relic.source.allowed_hosts', [
            'github.com',
            'api.github.com',
            'objects.githubusercontent.com',
            'release-assets.githubusercontent.com',
            'git.smelterworks.com',
            'example.test',
        ]);
        app()->forgetInstance(UpdateMirrorService::class);
    }

    /**
     * @param  array<string, mixed>  $stableRelease
     * @param  list<array<string, mixed>>  $extraReleases
     */
    protected function fakeAndWarmRelicMirror(array $stableRelease, array $extraReleases = []): void
    {
        $this->resetUpdateMirror();

        $allReleases = array_merge([$stableRelease], $extraReleases);
        $downloadUrls = [];

        foreach ($allReleases as $release) {
            foreach ($release['assets'] ?? [] as $asset) {
                if (is_array($asset) && filled($asset['browser_download_url'] ?? null)) {
                    $downloadUrls[(string) $asset['browser_download_url']] = 'fake-binary-payload';
                }
            }
        }

        Http::fake(function ($request) use ($stableRelease, $allReleases, $downloadUrls) {
            $url = $request->url();

            if (isset($downloadUrls[$url])) {
                return Http::response($downloadUrls[$url], 200);
            }

            if (str_ends_with($url, '/releases/latest')) {
                return Http::response($stableRelease, 200);
            }

            if (str_contains($url, '/releases')) {
                return Http::response($allReleases, 200);
            }

            return Http::response([], 404);
        });

        app(UpdateMirrorService::class)->warmProduct('relic');
    }

    protected function fakeRelicEmptyMirror(): void
    {
        $this->resetUpdateMirror();

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

        app(UpdateMirrorService::class)->warmProduct('relic');
    }
}
