<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_page_is_successful(): void
    {
        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Panel', false);
    }

    public function test_panel_redirects_only_to_https_external_url(): void
    {
        config(['smelterworks.links.panel' => 'https://panel.example.test/app']);

        $this->get(route('panel'))
            ->assertRedirect('https://panel.example.test/app');

        config(['smelterworks.links.panel' => 'http://insecure.example.test']);

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Panel', false);
    }

    public function test_contribute_page_links_to_github(): void
    {
        $github = rtrim(config('smelterworks.links.github'), '/');

        $this->get(route('contribute'))
            ->assertOk()
            ->assertSee('Contribute', false)
            ->assertSee($github, false)
            ->assertSee($github.'/SmelterWorks-Web/blob/main/CONTRIBUTING.md', false)
            ->assertSee('>GitHub<', false);
    }
}
