<?php

namespace Tests\Feature;

use App\Support\Hosting\HostingStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_includes_meta_open_graph_and_json_ld(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('images/brand/SmelterWorks-512.png', false);
    }

    public function test_relic_page_uses_preview_image_and_software_json_ld(): void
    {
        $this->get(route('relic'))
            ->assertOk()
            ->assertSee('home-relic-default.png', false)
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('Relic Launcher', false);
    }

    public function test_purchase_pages_are_noindex(): void
    {
        Config::set('smelterworks.hosting.coming_soon', false);
        app(HostingStockService::class)->syncFromConfig();

        $this->get(route('hosting.purchase', 'friends'))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
    }

    public function test_robots_txt_lists_sitemap_and_disallows_purchase_paths(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Allow: /', false)
            ->assertSee('Disallow: /hosting/*/purchase', false)
            ->assertSee('Disallow: /hosting/orders/', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_sitemap_lists_public_pages(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee(route('home'), false)
            ->assertSee(route('hosting'), false)
            ->assertSee(route('relic'), false)
            ->assertSee(route('privacy'), false)
            ->assertDontSee('/hosting/friends/purchase', false);
    }
}
