<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PanelPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_page_is_standalone_demo(): void
    {
        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('data-panel-demo', false)
            ->assertSee('Interactive preview', false)
            ->assertSee('Ember Hollow', false)
            ->assertSee('Pairing', false)
            ->assertSee('Cloud servers', false)
            ->assertSee('Paired servers', false)
            ->assertSee('Profile', false)
            ->assertSee('Settings', false)
            ->assertDontSee('site-header', false)
            ->assertDontSee('site-nav', false)
            ->assertDontSee('BYOS', false)
            ->assertDontSee('Stopped to save billing while world is exported', false)
            ->assertDontSee('Mod pack frozen at last boot', false)
            ->assertDontSee('Restore from last nightly when ready', false);
    }

    public function test_panel_demo_mods_proxy_returns_moddb_results(): void
    {
        Http::fake([
            'mods.vintagestory.at/api/mods*' => Http::response([
                'mods' => [
                    [
                        'modid' => 322,
                        'modidstrs' => ['primitivesurvival'],
                        'name' => 'Primitive Survival',
                        'author' => 'SpearAndFang',
                        'summary' => 'Survival expansion.',
                        'logo' => 'https://moddbcdn.vintagestory.at/logo3.png',
                        'downloads' => 930530,
                        'tags' => ['Survival'],
                        'urlalias' => 'primitivesurvival',
                    ],
                ],
            ], 200),
        ]);

        $this->get(route('panel.demo.mods', ['q' => 'primitive']))
            ->assertOk()
            ->assertJsonPath('mods.0.modid', 'primitivesurvival')
            ->assertJsonPath('mods.0.name', 'Primitive Survival')
            ->assertJsonPath('mods.0.logo', 'https://moddbcdn.vintagestory.at/logo3.png')
            ->assertJsonPath('mods.0.downloads', 930530);
    }

    public function test_panel_demo_mods_proxy_can_browse_without_query(): void
    {
        Http::fake([
            'mods.vintagestory.at/api/mods*' => Http::response([
                'mods' => [
                    [
                        'modid' => 792,
                        'modidstrs' => ['betterruins'],
                        'name' => 'BetterRuins',
                        'author' => 'NiclAss',
                        'summary' => 'Adds ruins.',
                        'logo' => 'https://moddbcdn.vintagestory.at/betterruins.png',
                        'downloads' => 1096953,
                        'tags' => ['Worldgen'],
                        'urlalias' => 'betterruins',
                    ],
                ],
            ], 200),
        ]);

        $this->get(route('panel.demo.mods', ['orderby' => 'downloads']))
            ->assertOk()
            ->assertJsonPath('mods.0.modid', 'betterruins')
            ->assertJsonPath('mods.0.name', 'BetterRuins');
    }

    public function test_panel_redirects_only_to_https_external_url(): void
    {
        config(['smelterworks.links.panel' => 'https://panel.example.test/app']);

        $this->get(route('panel'))
            ->assertRedirect('https://panel.example.test/app');

        config(['smelterworks.links.panel' => 'http://insecure.example.test']);

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('data-panel-demo', false);
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
