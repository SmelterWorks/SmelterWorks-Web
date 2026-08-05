<?php

namespace App\Support\Currency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExchangeRateService
{
    public function __construct(
        private readonly string $endpoint = 'https://api.frankfurter.app/latest',
    ) {}

    /**
     * ECB reference rate from Frankfurter (no API key). Cached for six hours.
     */
    public function usdToEur(): float
    {
        return (float) Cache::remember('exchange.usd_to_eur', now()->addHours(6), function (): float {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->retry(2, 150)
                ->acceptJson()
                ->get($this->endpoint, [
                    'from' => 'USD',
                    'to' => 'EUR',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Frankfurter exchange request failed with HTTP '.$response->status());
            }

            $rate = data_get($response->json(), 'rates.EUR');

            if (! is_numeric($rate) || (float) $rate <= 0) {
                throw new RuntimeException('Frankfurter response did not include a usable EUR rate.');
            }

            return round((float) $rate, 6);
        });
    }

    public function convertUsdToEur(float|int $usd): float
    {
        return round((float) $usd * $this->usdToEur(), 2);
    }

    /**
     * @return array{rate: float, as_of: string|null, source: string, available: bool}
     */
    public function quote(): array
    {
        try {
            $rate = $this->usdToEur();

            return [
                'rate' => $rate,
                'as_of' => now()->toDateString(),
                'source' => 'Frankfurter / ECB',
                'available' => true,
            ];
        } catch (\Throwable $exception) {
            Log::warning('USD to EUR rate unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'rate' => 0.0,
                'as_of' => null,
                'source' => 'Frankfurter / ECB',
                'available' => false,
            ];
        }
    }
}
