<?php

namespace App\Support\Currency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange.usd_to_eur';

    public function __construct(
        private readonly string $endpoint = 'https://api.frankfurter.app/latest',
    ) {}

    /**
     * ECB reference rate from Frankfurter (no API key).
     * Fresh for six hours, stale copy kept for up to seven days, then config fallback.
     */
    public function usdToEur(): float
    {
        $cached = $this->normalizeCached(Cache::get(self::CACHE_KEY));

        if ($cached !== null && $this->isFresh($cached)) {
            return (float) $cached['rate'];
        }

        if ($cached !== null && $this->isStaleUsable($cached)) {
            return (float) $cached['rate'];
        }

        return $this->fetchOrFallback();
    }

    public function warmCache(): void
    {
        $cached = $this->normalizeCached(Cache::get(self::CACHE_KEY));

        if ($cached !== null && $this->isFresh($cached)) {
            return;
        }

        $lock = Cache::lock(self::CACHE_KEY.'.refresh', 30);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->fetchAndStore();
        } catch (\Throwable $exception) {
            Log::warning('Scheduled USD to EUR refresh failed.', [
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
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
            $cached = $this->normalizeCached(Cache::get(self::CACHE_KEY));

            return [
                'rate' => $rate,
                'as_of' => $cached['as_of'] ?? null,
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

    private function fetchOrFallback(): float
    {
        try {
            return $this->fetchAndStore();
        } catch (\Throwable $exception) {
            $fallback = $this->fallbackRate();

            if ($fallback !== null) {
                Log::warning('USD to EUR fetch failed. Using configured fallback rate.', [
                    'message' => $exception->getMessage(),
                    'fallback_rate' => $fallback,
                ]);

                $this->remember([
                    'rate' => $fallback,
                    'as_of' => null,
                    'cached_at' => now()->getTimestamp(),
                    'fallback' => true,
                ]);

                return $fallback;
            }

            throw $exception;
        }
    }

    private function fetchAndStore(): float
    {
        $response = Http::timeout(3)
            ->connectTimeout(2)
            ->retry(1, 100, throw: false)
            ->acceptJson()
            ->get($this->endpoint, [
                'from' => 'USD',
                'to' => 'EUR',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Frankfurter exchange request failed with HTTP '.$response->status());
        }

        $body = $response->json();
        $rate = data_get($body, 'rates.EUR');
        $date = data_get($body, 'date');

        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Frankfurter response did not include a usable EUR rate.');
        }

        $rounded = round((float) $rate, 6);

        $this->remember([
            'rate' => $rounded,
            'as_of' => is_string($date) ? $date : null,
            'cached_at' => now()->getTimestamp(),
            'fallback' => false,
        ]);

        return $rounded;
    }

    /**
     * @param  array{rate: float, as_of: string|null, cached_at: int, fallback?: bool}  $payload
     */
    private function remember(array $payload): void
    {
        Cache::put(self::CACHE_KEY, $payload, now()->addSeconds($this->staleCacheSeconds()));
    }

    /**
     * @return array{rate: float, as_of: string|null, cached_at: int, fallback?: bool}|null
     */
    private function normalizeCached(mixed $cached): ?array
    {
        if (is_array($cached) && isset($cached['rate']) && is_numeric($cached['rate'])) {
            return $cached;
        }

        if (is_numeric($cached) && (float) $cached > 0) {
            $payload = [
                'rate' => round((float) $cached, 6),
                'as_of' => null,
                'cached_at' => now()->getTimestamp(),
            ];

            $this->remember($payload);

            return $payload;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isFresh(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0
            && is_numeric($cached['rate'])
            && (float) $cached['rate'] > 0
            && (now()->getTimestamp() - $cachedAt) <= $this->cacheSeconds();
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isStaleUsable(array $cached): bool
    {
        $cachedAt = (int) ($cached['cached_at'] ?? 0);

        return $cachedAt > 0
            && is_numeric($cached['rate'])
            && (float) $cached['rate'] > 0
            && (now()->getTimestamp() - $cachedAt) <= $this->staleCacheSeconds();
    }

    private function cacheSeconds(): int
    {
        return max(60, (int) config('smelterworks.hosting.exchange.cache_seconds', 21600));
    }

    private function staleCacheSeconds(): int
    {
        return max($this->cacheSeconds(), (int) config('smelterworks.hosting.exchange.stale_seconds', 604800));
    }

    private function fallbackRate(): ?float
    {
        $rate = config('smelterworks.hosting.exchange.fallback_usd_to_eur');

        if (! is_numeric($rate) || (float) $rate <= 0) {
            return null;
        }

        return round((float) $rate, 6);
    }
}
