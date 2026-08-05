<?php

namespace Tests\Unit;

use Tests\TestCase;

class SmelterworksConfigTest extends TestCase
{
    public function test_relic_platform_config_uses_structured_entries(): void
    {
        foreach (config('smelterworks.relic.platforms') as $platform) {
            $this->assertIsArray($platform);
            $this->assertArrayHasKey('icon', $platform);
            $this->assertArrayHasKey('label', $platform);
            $this->assertNotSame('', $platform['label']);
        }
    }

    public function test_hosting_is_marked_coming_soon_in_default_config(): void
    {
        $this->assertTrue((bool) config('smelterworks.hosting.coming_soon'));
    }

    public function test_hosting_home_highlights_has_six_cards(): void
    {
        $highlights = config('smelterworks.hosting.home_highlights');

        $this->assertIsArray($highlights);
        $this->assertCount(6, $highlights);

        foreach ($highlights as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('text', $item);
            $this->assertNotSame('', $item['title']);
            $this->assertNotSame('', $item['text']);
        }
    }

    public function test_banner_defaults_to_under_construction(): void
    {
        $banner = config('smelterworks.banner');

        $this->assertTrue((bool) $banner['enabled']);
        $this->assertSame('Website is under construction', $banner['message']);
        $this->assertSame('#b45309', $banner['background']);
        $this->assertSame('#ebe4d8', $banner['color']);
    }

    public function test_relic_upcoming_does_not_mention_hosting_integration(): void
    {
        $upcoming = config('smelterworks.relic.upcoming');

        $this->assertIsArray($upcoming);

        $text = array_map(
            fn (array $item): string => $item['text'],
            $upcoming,
        );

        $this->assertNotContains('Server hosting integration', $text);
    }
}
