<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_branding_page_lists_smelterworks_marks_with_format_toggles(): void
    {
        $this->get(route('branding'))
            ->assertOk()
            ->assertSee('Branding', false)
            ->assertSee('Master marks', false)
            ->assertSee('Logo mark (solid)', false)
            ->assertSee('Logo mark (transparent)', false)
            ->assertSee('1024×1024 px', false)
            ->assertSee('64×64 px', false)
            ->assertSee('data-branding-format="png"', false)
            ->assertSee('data-branding-format="webp"', false)
            ->assertSee('Download PNG', false)
            ->assertDontSee('Lucide', false)
            ->assertDontSee('Usage', false)
            ->assertDontSee('#c45c26', false);
    }
}
