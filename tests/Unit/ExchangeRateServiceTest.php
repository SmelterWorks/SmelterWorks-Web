<?php

namespace Tests\Unit;

use App\Support\Currency\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    protected bool $fakeFrankfurterRates = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_fetches_and_caches_usd_to_eur_rate(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => '2026-08-04',
                'rates' => ['EUR' => 0.86843],
            ], 200),
        ]);

        $service = app(ExchangeRateService::class);

        $this->assertSame(0.86843, $service->usdToEur());
        $this->assertSame(8.68, $service->convertUsdToEur(10));

        $service->usdToEur();

        Http::assertSentCount(1);
    }

    public function test_quote_uses_ecb_date_from_response(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => '2026-08-04',
                'rates' => ['EUR' => 0.86843],
            ], 200),
        ]);

        $quote = app(ExchangeRateService::class)->quote();

        $this->assertTrue($quote['available']);
        $this->assertSame('2026-08-04', $quote['as_of']);
    }

    public function test_quote_marks_unavailable_when_request_fails_without_cache_or_fallback(): void
    {
        config([
            'smelterworks.hosting.exchange.fallback_usd_to_eur' => 0,
        ]);

        Http::fake([
            'api.frankfurter.app/*' => Http::response('nope', 503),
        ]);

        $quote = app(ExchangeRateService::class)->quote();

        $this->assertFalse($quote['available']);
        $this->assertSame(0.0, $quote['rate']);
    }

    public function test_failed_fetch_serves_stale_cache(): void
    {
        Cache::put('exchange.usd_to_eur', [
            'rate' => 0.85,
            'as_of' => '2026-08-01',
            'cached_at' => now()->subHours(8)->getTimestamp(),
        ], now()->addDay());

        Http::fake([
            'api.frankfurter.app/*' => Http::response('nope', 503),
        ]);

        $service = app(ExchangeRateService::class);

        $this->assertSame(0.85, $service->usdToEur());
        Http::assertNothingSent();
    }

    public function test_stale_cache_returns_immediately_without_network(): void
    {
        Cache::put('exchange.usd_to_eur', [
            'rate' => 0.85,
            'as_of' => '2026-08-01',
            'cached_at' => now()->subHours(8)->getTimestamp(),
        ], now()->addDay());

        Http::fake();

        $this->assertSame(0.85, app(ExchangeRateService::class)->usdToEur());
        Http::assertNothingSent();
    }

    public function test_failed_fetch_uses_configured_fallback_on_cold_start(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response('nope', 503),
        ]);

        $service = app(ExchangeRateService::class);

        $this->assertSame(0.92, $service->usdToEur());
        Http::assertSentCount(1);
    }
}
