<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1?: array<string, string>}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['home'],
            'hosting' => ['hosting'],
            'hosting feed' => ['hosting.feed'],
            'mods' => ['mods'],
            'relic' => ['relic'],
            'relic download' => ['relic.download'],
            'projects index' => ['projects.index'],
            'project show' => ['projects.show', ['slug' => 'relic-launcher']],
            'about' => ['about'],
            'branding' => ['branding'],
            'contact' => ['contact'],
            'donate' => ['donate'],
            'contribute' => ['contribute'],
            'privacy' => ['privacy'],
            'terms' => ['terms'],
            'panel' => ['panel'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_get_routes_render_successfully(string $route, array $parameters = []): void
    {
        $this->get(route($route, $parameters))->assertOk();
    }

    public function test_unknown_project_returns_not_found(): void
    {
        $this->get(route('projects.show', 'missing-project'))
            ->assertNotFound();
    }

    public function test_hosting_purchase_redirects_while_coming_soon(): void
    {
        $this->get(route('hosting.purchase', 'modded'))
            ->assertRedirect(route('hosting'));
    }
}
