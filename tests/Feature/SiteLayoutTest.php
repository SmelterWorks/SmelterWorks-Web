<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SiteLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_shows_fluxer_github_and_forgejo_without_panel_button(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-label="Switch to dark theme"', false)
            ->assertSee('images/brand/fluxer.png', false)
            ->assertSee(config('smelterworks.links.fluxer'), false)
            ->assertSee(config('smelterworks.links.github'), false)
            ->assertSee(config('smelterworks.links.forgejo'), false)
            ->assertSee('aria-label="GitHub"', false)
            ->assertSee('aria-label="Forgejo"', false)
            ->assertSee('data-menu-toggle', false)
            ->assertSee('mobile-nav', false)
            ->assertSee(route('donate'), false)
            ->assertSee('>Donate<', false)
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
            ->assertHeader('Content-Security-Policy')
            ->assertDontSee('fonts.bunny.net', false);
    }

    public function test_content_security_policy_allows_vite_when_hot(): void
    {
        $hotFile = public_path('hot');
        file_put_contents($hotFile, 'http://127.0.0.1:5173');

        try {
            $csp = (string) $this->get(route('home'))->headers->get('Content-Security-Policy');

            $this->assertStringContainsString(
                "script-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173",
                $csp,
            );
            $this->assertStringContainsString(
                "style-src 'self' 'unsafe-inline' http://127.0.0.1:5173",
                $csp,
            );
            $this->assertStringContainsString(
                "font-src 'self' data: http://127.0.0.1:5173",
                $csp,
            );
        } finally {
            if (is_file($hotFile)) {
                unlink($hotFile);
            }
        }
    }

    public function test_content_security_policy_restricts_scripts_without_vite_hot(): void
    {
        $hotFile = public_path('hot');

        if (is_file($hotFile)) {
            unlink($hotFile);
        }

        $csp = (string) $this->get(route('home'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self';", $csp);
        $this->assertStringNotContainsString('5173', $csp);
    }

    public function test_https_app_url_forces_https_asset_urls(): void
    {
        config(['app.url' => 'https://smelterworks.com']);
        URL::forceRootUrl('https://smelterworks.com');
        URL::forceScheme('https');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://smelterworks.com/images/brand/', false)
            ->assertDontSee('http://smelterworks.com/images/brand/', false);
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

    public function test_html_includes_theme_bootstrap_script(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('scripts/theme-init.js'), false);
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
