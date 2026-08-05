<?php

namespace Tests\Feature;

use App\Support\Hosting\HostingStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function brandedErrorPages(): array
    {
        return [
            '401' => [401, 'Sign in required'],
            '403' => [403, 'Access denied'],
            '404' => [404, 'Page not found'],
            '405' => [405, 'Method not allowed'],
            '419' => [419, 'Session expired'],
            '429' => [429, 'Too many requests'],
            '500' => [500, 'Server error'],
            '503' => [503, 'Back soon'],
        ];
    }

    #[DataProvider('brandedErrorPages')]
    public function test_branded_error_pages_use_site_layout(int $status, string $heading): void
    {
        Route::get('/__error-probe/{status}', fn (int $status) => abort($status))
            ->middleware('web');

        $response = $this->get('/__error-probe/'.$status);

        $response
            ->assertStatus($status)
            ->assertSee('error-page__code', false)
            ->assertSee((string) $status, false)
            ->assertSee($heading, false)
            ->assertSee('site-shell', false)
            ->assertSee('id="main"', false)
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_unknown_project_returns_branded_not_found_page(): void
    {
        $this->get(route('projects.show', 'missing-project'))
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('error-page__code', false);
    }

    public function test_unknown_route_returns_branded_not_found_page(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found', false);
    }

    public function test_post_to_get_route_returns_method_not_allowed_page(): void
    {
        $this->post(route('home'))
            ->assertStatus(405)
            ->assertSee('Method not allowed', false);
    }

    public function test_page_expired_view_uses_branded_layout(): void
    {
        $html = view('errors.419')->render();

        $this->assertStringContainsString('Session expired', $html);
        $this->assertStringContainsString('error-page__code', $html);
        $this->assertStringContainsString('419', $html);
        $this->assertStringContainsString('site-shell', $html);
        $this->assertStringContainsString('name="robots" content="noindex, nofollow"', $html);
    }

    public function test_rate_limit_returns_too_many_requests_page(): void
    {
        Config::set('smelterworks.hosting.coming_soon', false);
        app(HostingStockService::class)->syncFromConfig();

        $payload = [
            'plan_slug' => 'friends',
            'region_code' => 'us',
            'billing_cycle' => 'monthly',
            'customer_name' => 'Alex Tester',
            'customer_email' => 'alex@example.com',
        ];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('hosting.purchase.store'), $payload)->assertRedirect();
        }

        $this->post(route('hosting.purchase.store'), $payload)
            ->assertStatus(429)
            ->assertSee('Too many requests', false);
    }

    public function test_maintenance_mode_returns_service_unavailable_page(): void
    {
        Artisan::call('down', ['--retry' => 60]);

        try {
            $this->get(route('home'))
                ->assertStatus(503)
                ->assertSee('Back soon', false);
        } finally {
            Artisan::call('up');
        }
    }

    public function test_unmapped_client_error_uses_generic_page(): void
    {
        Route::get('/__error-probe/{status}', fn (int $status) => abort($status))
            ->middleware('web');

        $this->get('/__error-probe/410')
            ->assertStatus(410)
            ->assertSee('Gone', false)
            ->assertSee('error-page__code', false)
            ->assertSee('410', false);
    }
}
