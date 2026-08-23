<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_index_lists_published_projects(): void
    {
        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Better Sprinting', false)
            ->assertSee(route('projects.show', 'better-sprinting'), false)
            ->assertSee('Relic Launcher', false)
            ->assertSee(route('relic'), false)
            ->assertDontSee('Forge Kit', false)
            ->assertDontSee('Hearthside', false)
            ->assertDontSee('Slag Lib', false);
    }

    public function test_project_show_page_renders_relic_launcher(): void
    {
        $this->get(route('projects.show', 'relic-launcher'))
            ->assertOk()
            ->assertSee('Relic Launcher', false)
            ->assertSee('VS ModDB', false);
    }

    public function test_project_show_page_renders_better_sprinting(): void
    {
        $this->get(route('projects.show', 'better-sprinting'))
            ->assertOk()
            ->assertSee('Better Sprinting', false)
            ->assertSee('Ctrl+Shift+R', false)
            ->assertSee('https://github.com/SmelterWorks/BetterSprinting', false);
    }

    public function test_home_page_lists_featured_projects(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Better Sprinting', false)
            ->assertSee(route('projects.show', 'better-sprinting'), false)
            ->assertSee('Relic Launcher', false)
            ->assertSee(route('relic'), false)
            ->assertSee('images/brand/SmelterWorks-64.webp', false)
            ->assertDontSee('brand-logo--hero', false);
    }
}
