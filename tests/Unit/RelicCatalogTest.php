<?php

namespace Tests\Unit;

use App\Support\Relic\RelicCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RelicCatalogTest extends TestCase
{
    private RelicCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = new RelicCatalog;
    }

    public function test_normalize_platforms_accepts_structured_entries(): void
    {
        $platforms = $this->catalog->normalizePlatforms([
            ['icon' => 'windows', 'label' => 'Windows', 'detail' => '10+ x64'],
            ['icon' => 'linux', 'label' => 'Linux', 'detail' => 'x64'],
        ]);

        $this->assertSame('windows', $platforms[0]['icon']);
        $this->assertSame('Windows', $platforms[0]['label']);
        $this->assertSame('linux', $platforms[1]['icon']);
    }

    #[DataProvider('legacyPlatformLabels')]
    public function test_normalize_platforms_accepts_legacy_string_labels(string $label, string $icon, string $name): void
    {
        $platforms = $this->catalog->normalizePlatforms([$label]);

        $this->assertSame($icon, $platforms[0]['icon']);
        $this->assertSame($name, $platforms[0]['label']);
        $this->assertSame($label, $platforms[0]['detail']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function legacyPlatformLabels(): array
    {
        return [
            'windows' => ['Windows 10+ x64', 'windows', 'Windows'],
            'linux' => ['Linux x64 (X11 and native Wayland)', 'linux', 'Linux'],
            'macos' => ['macOS 13+ (x64 and arm64)', 'macos', 'macOS'],
        ];
    }

    public function test_for_view_never_returns_string_platform_entries(): void
    {
        config([
            'smelterworks.relic' => array_merge(config('smelterworks.relic'), [
                'platforms' => [
                    'Windows 10+ x64',
                    'Linux x64 (X11 and native Wayland)',
                ],
            ]),
        ]);

        $relic = $this->catalog->forView();

        foreach ($relic['platforms'] as $platform) {
            $this->assertIsArray($platform);
            $this->assertArrayHasKey('icon', $platform);
            $this->assertArrayHasKey('label', $platform);
        }
    }

    public function test_normalize_platforms_drops_blank_labels(): void
    {
        $platforms = $this->catalog->normalizePlatforms([
            ['icon' => 'windows', 'label' => '', 'detail' => null],
            ['icon' => 'linux', 'label' => 'Linux', 'detail' => null],
        ]);

        $this->assertCount(1, $platforms);
        $this->assertSame('Linux', $platforms[0]['label']);
    }
}
