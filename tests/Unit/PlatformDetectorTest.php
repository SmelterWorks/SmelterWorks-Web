<?php

namespace Tests\Unit;

use App\Support\Platform\PlatformDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlatformDetectorTest extends TestCase
{
    #[DataProvider('userAgents')]
    public function test_detects_platform(string $userAgent, string $id, string $family): void
    {
        $detected = (new PlatformDetector)->detect($userAgent);

        $this->assertSame($id, $detected['id']);
        $this->assertSame($family, $detected['family']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function userAgents(): array
    {
        return [
            'windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'windows',
                'windows',
            ],
            'linux' => [
                'Mozilla/5.0 (X11; Linux x86_64)',
                'linux',
                'linux',
            ],
            'macos intel' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'macos-intel',
                'macos',
            ],
            'macos arm' => [
                'Mozilla/5.0 (Macintosh; ARM Mac OS X 14_0)',
                'macos-arm',
                'macos',
            ],
            'empty' => [
                '',
                'unknown',
                'unknown',
            ],
        ];
    }
}
