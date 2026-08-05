<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_projects_section_without_marketing_lede(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Projects', false)
            ->assertDontSee('What we ship', false)
            ->assertDontSee('Source on GitHub. Chat on Fluxer.', false)
            ->assertDontSee('Launcher, hosting, and mods.', false);
    }

    public function test_mods_page_stays_mods_only(): void
    {
        $this->get(route('mods'))
            ->assertOk()
            ->assertSee('Vintage Story mods we publish.', false)
            ->assertSee('No mods yet', false)
            ->assertDontSee('hosting tools', false)
            ->assertDontSee('Hosted servers', false)
            ->assertDontSee('Relic Launcher covers', false)
            ->assertDontSee('one-click installs', false);
    }

    public function test_about_page_focuses_on_project_story(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Ivan (Sudo-Ivan)', false)
            ->assertSee('paid directly out of pocket', false)
            ->assertSee(route('donate'), false)
            ->assertDontSee('What we build', false);
    }

    public function test_contact_page_stays_support_focused(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('contact@example[dot]test', false)
            ->assertSee('Questions about mods, Relic Launcher, contributions, or support.', false)
            ->assertDontSee('Questions about hosting', false);
    }
}
