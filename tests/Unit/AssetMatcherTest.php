<?php

namespace Tests\Unit;

use App\Support\Updates\AssetMatcher;
use App\Support\Updates\Data\UpstreamAsset;
use App\Support\Updates\Data\UpstreamRelease;
use Tests\TestCase;

class AssetMatcherTest extends TestCase
{
    public function test_matches_windows_zip_preferring_win_x64_without_app_zip(): void
    {
        $release = new UpstreamRelease(
            tag: 'v1.0.0',
            version: '1.0.0',
            htmlUrl: 'https://example.test/releases/tag/v1.0.0',
            publishedAt: null,
            assets: [
                new UpstreamAsset('relic-launcher-1.0.0-win-x64.app.zip', 'https://example.test/app.zip'),
                new UpstreamAsset('relic-launcher-1.0.0-win-x64.zip', 'https://example.test/win.zip'),
            ],
        );

        $matches = app(AssetMatcher::class)->match($release, [
            [
                'rid' => 'win-x64',
                'installKind' => 'WindowsZip',
                'match' => ['*.zip'],
                'prefer' => ['*win-x64*'],
                'reject' => ['*.app.zip'],
            ],
        ]);

        $this->assertCount(1, $matches);
        $this->assertSame('relic-launcher-1.0.0-win-x64.zip', $matches[0]['asset']->name);
        $this->assertSame('https://example.test/win.zip', $matches[0]['asset']->downloadUrl);
    }

    public function test_matches_linux_appimage_before_tar_gz(): void
    {
        $release = new UpstreamRelease(
            tag: 'v1.0.0',
            version: '1.0.0',
            htmlUrl: 'https://example.test/releases/tag/v1.0.0',
            publishedAt: null,
            assets: [
                new UpstreamAsset('relic-launcher-1.0.0-linux-x64.tar.gz', 'https://example.test/linux.tar.gz'),
                new UpstreamAsset('relic-launcher-1.0.0-linux-x64.AppImage', 'https://example.test/linux.AppImage'),
            ],
        );

        $matches = app(AssetMatcher::class)->match($release, [
            [
                'rid' => 'linux-x64',
                'installKind' => 'LinuxAppImage',
                'match' => ['*.AppImage', '*.appimage'],
                'prefer' => ['*linux-x64*'],
                'reject' => [],
            ],
            [
                'rid' => 'linux-x64',
                'installKind' => 'LinuxTarGz',
                'match' => ['*.tar.gz'],
                'prefer' => ['*linux-x64*'],
                'reject' => [],
            ],
        ]);

        $this->assertCount(2, $matches);
        $this->assertSame('LinuxAppImage', $matches[0]['installKind']);
        $this->assertSame('LinuxTarGz', $matches[1]['installKind']);
    }
}
