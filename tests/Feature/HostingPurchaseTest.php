<?php

namespace Tests\Feature;

use App\Models\HostingPurchase;
use App\Models\HostingStock;
use App\Support\Hosting\HostingStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HostingPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosting_page_is_coming_soon_by_default(): void
    {
        $this->get(route('hosting'))
            ->assertOk()
            ->assertSee('Coming soon', false)
            ->assertDontSee('>Purchase<', false)
            ->assertDontSee('left', false);
    }

    public function test_purchase_routes_redirect_while_coming_soon(): void
    {
        $this->get(route('hosting.purchase', 'modded'))
            ->assertRedirect(route('hosting'));

        $this->post(route('hosting.purchase.store'), [
            'plan_slug' => 'friends',
            'region_code' => 'us',
            'billing_cycle' => 'monthly',
            'customer_name' => 'Alex Tester',
            'customer_email' => 'alex@example.com',
            'server_name' => 'Test World',
        ])->assertRedirect(route('hosting'));

        $this->assertSame(0, HostingPurchase::query()->count());
    }

    public function test_hosting_page_shows_purchase_and_stock_when_open(): void
    {
        $this->openHostingPurchases();

        $this->get(route('hosting'))
            ->assertOk()
            ->assertSee('Purchase', false)
            ->assertSee('left', false)
            ->assertSee('United States', false)
            ->assertDontSee('Open panel', false);
    }

    public function test_purchase_form_is_available_for_a_plan_when_open(): void
    {
        $this->openHostingPurchases();

        $this->get(route('hosting.purchase', 'modded'))
            ->assertOk()
            ->assertSee('Purchase Modded', false)
            ->assertSee('Europe (Germany)', false);
    }

    public function test_purchase_reserves_stock_when_open(): void
    {
        $this->openHostingPurchases();

        $before = HostingStock::query()
            ->where('region_code', 'us')
            ->where('plan_slug', 'friends')
            ->firstOrFail();

        $response = $this->post(route('hosting.purchase.store'), [
            'plan_slug' => 'friends',
            'region_code' => 'us',
            'billing_cycle' => 'monthly',
            'customer_name' => 'Alex Tester',
            'customer_email' => 'alex@example.com',
            'server_name' => 'Test World',
        ]);

        $purchase = HostingPurchase::query()->firstOrFail();

        $response->assertRedirect(route('hosting.purchases.show', $purchase));

        $this->assertSame(HostingPurchase::STATUS_PENDING, $purchase->status);
        $this->assertSame($before->sold + 1, $before->fresh()->sold);
    }

    public function test_sold_out_region_is_rejected_when_open(): void
    {
        $this->openHostingPurchases();

        HostingStock::query()
            ->where('region_code', 'eu-de')
            ->where('plan_slug', 'heavy')
            ->update(['sold' => 1, 'capacity' => 1]);

        $this->from(route('hosting.purchase', 'heavy'))
            ->post(route('hosting.purchase.store'), [
                'plan_slug' => 'heavy',
                'region_code' => 'eu-de',
                'billing_cycle' => 'yearly',
                'customer_name' => 'Alex Tester',
                'customer_email' => 'alex@example.com',
            ])
            ->assertRedirect(route('hosting.purchase', 'heavy'))
            ->assertSessionHasErrors('region_code');
    }

    public function test_stock_matches_region_capacities(): void
    {
        app(HostingStockService::class)->syncFromConfig();

        $this->assertSame(8, HostingStock::query()->where('plan_slug', 'friends')->sum('capacity'));
        $this->assertSame(6, HostingStock::query()->where('plan_slug', 'modded')->sum('capacity'));
        $this->assertSame(2, HostingStock::query()->where('plan_slug', 'heavy')->sum('capacity'));
    }

    private function openHostingPurchases(): void
    {
        Config::set('smelterworks.hosting.coming_soon', false);
        app(HostingStockService::class)->syncFromConfig();
    }
}
