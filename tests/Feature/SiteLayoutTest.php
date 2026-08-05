<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_shows_fluxer_and_github_without_panel_button(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('images/brand/fluxer.png', false)
            ->assertSee(config('smelterworks.links.fluxer'), false)
            ->assertSee('data-menu-toggle', false)
            ->assertSee('mobile-nav', false)
            ->assertDontSee('Join in', false)
            ->assertDontSee('>Panel<', false);
    }

    public function test_footer_includes_legal_and_donate_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee(route('contact'), false)
            ->assertSee(route('donate'), false)
            ->assertDontSee('>Panel<', false);
    }

    public function test_responses_send_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_site_banner_shows_when_enabled(): void
    {
        config([
            'smelterworks.banner.enabled' => true,
            'smelterworks.banner.message' => 'Website is under construction',
            'smelterworks.banner.background' => '#b45309',
            'smelterworks.banner.color' => '#ebe4d8',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('site-banner', false)
            ->assertSee('Website is under construction', false)
            ->assertSee('--site-banner-bg: #b45309', false)
            ->assertSee('--site-banner-fg: #ebe4d8', false);
    }

    public function test_site_banner_hides_when_disabled(): void
    {
        config([
            'smelterworks.banner.enabled' => false,
            'smelterworks.banner.message' => 'Website is under construction',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('site-banner', false)
            ->assertDontSee('Website is under construction', false);
    }
}
