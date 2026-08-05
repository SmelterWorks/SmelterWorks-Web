<?php

namespace Tests\Feature;

use App\Models\HostingStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosting_page_lists_plans_as_coming_soon(): void
    {
        $this->get(route('hosting'))
            ->assertOk()
            ->assertSee('Friends', false)
            ->assertSee('Modded', false)
            ->assertSee('Heavy', false)
            ->assertSee('Coming soon', false)
            ->assertSee('Affordable and friendly but limited hosting.', false)
            ->assertDontSee('Checkout opens when the panel is ready.', false)
            ->assertSee(route('hosting.feed'), false)
            ->assertSee('aria-label="Hosting RSS feed"', false)
            ->assertDontSee('Plan prices, stock, and availability', false)
            ->assertSee('United States', false)
            ->assertSee('Europe (Germany)', false)
            ->assertSee('data-region-map', false)
            ->assertSee('data-region-map-markers', false)
            ->assertSee('Quality servers with efficient, lower-waste hosting', false)
            ->assertSee('SFTP and HTTP file uploads', false)
            ->assertSee('Features and plans are subject to change until we are open.', false)
            ->assertSee('Monthly plans: full refund within 7 days', false)
            ->assertSee('Prices are USD.', false)
            ->assertDontSee('>Purchase<', false)
            ->assertDontSee('Fleet:', false)
            ->assertDontSee('2 hosts', false);
    }

    public function test_hosting_index_does_not_touch_stock_while_coming_soon(): void
    {
        $this->assertSame(0, HostingStock::query()->count());

        $this->get(route('hosting'))->assertOk();

        $this->assertSame(0, HostingStock::query()->count());
    }

    public function test_hosting_rss_feed_lists_plans(): void
    {
        $this->get(route('hosting.feed'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<rss version="2.0">', false)
            ->assertSee('Friends: $10/mo (Coming soon)', false)
            ->assertSee('Modded: $15/mo (Coming soon)', false)
            ->assertSee('Heavy: $25/mo (Coming soon)', false)
            ->assertSee('Affordable and friendly but limited hosting.', false);
    }
}
