<?php

namespace Tests\Unit;

use App\Support\Content\BrandingCatalog;
use Tests\TestCase;

class BrandingCatalogTest extends TestCase
{
    public function test_catalog_groups_formats_for_each_mark_size(): void
    {
        $page = app(BrandingCatalog::class)->forPage();

        $solidGroup = collect($page['groups'])->firstWhere('title', 'Logo mark (solid)');

        $this->assertNotNull($solidGroup);

        $sixtyFour = collect($solidGroup['marks'])->firstWhere('label', '64×64 px');

        $this->assertNotNull($sixtyFour);
        $this->assertArrayHasKey('png', $sixtyFour['variants']);
        $this->assertArrayHasKey('webp', $sixtyFour['variants']);
        $this->assertSame('PNG', $sixtyFour['variants']['png']['format']);
        $this->assertSame('WEBP', $sixtyFour['variants']['webp']['format']);
    }

    public function test_catalog_master_marks_only_include_png(): void
    {
        $page = app(BrandingCatalog::class)->forPage();

        $masterGroup = collect($page['groups'])->firstWhere('title', 'Master marks');

        $this->assertNotNull($masterGroup);

        $master = collect($masterGroup['marks'])->firstWhere('label', '1024×1024 px');

        $this->assertNotNull($master);
        $this->assertArrayHasKey('png', $master['variants']);
        $this->assertArrayNotHasKey('webp', $master['variants']);
    }
}
