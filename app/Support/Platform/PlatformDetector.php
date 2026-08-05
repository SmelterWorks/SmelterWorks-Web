<?php

namespace App\Support\Platform;

class PlatformDetector
{
    /**
     * @return array{id: string, label: string, family: string}
     */
    public function detect(?string $userAgent): array
    {
        $ua = strtolower((string) $userAgent);

        if ($ua === '') {
            return [
                'id' => 'unknown',
                'label' => 'Unknown',
                'family' => 'unknown',
            ];
        }

        if (str_contains($ua, 'android')) {
            return [
                'id' => 'linux',
                'label' => 'Linux',
                'family' => 'linux',
            ];
        }

        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            return [
                'id' => 'macos-arm',
                'label' => 'macOS (Apple Silicon)',
                'family' => 'macos',
            ];
        }

        if (str_contains($ua, 'mac os x') || str_contains($ua, 'macintosh')) {
            if (str_contains($ua, 'arm') || str_contains($ua, 'aarch64')) {
                return [
                    'id' => 'macos-arm',
                    'label' => 'macOS (Apple Silicon)',
                    'family' => 'macos',
                ];
            }

            return [
                'id' => 'macos-intel',
                'label' => 'macOS (Intel)',
                'family' => 'macos',
            ];
        }

        if (str_contains($ua, 'windows')) {
            return [
                'id' => 'windows',
                'label' => 'Windows',
                'family' => 'windows',
            ];
        }

        if (str_contains($ua, 'linux') || str_contains($ua, 'cros') || str_contains($ua, 'ubuntu') || str_contains($ua, 'fedora')) {
            return [
                'id' => 'linux',
                'label' => 'Linux',
                'family' => 'linux',
            ];
        }

        return [
            'id' => 'unknown',
            'label' => 'Unknown',
            'family' => 'unknown',
        ];
    }
}
