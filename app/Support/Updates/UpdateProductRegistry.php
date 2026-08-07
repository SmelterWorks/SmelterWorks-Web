<?php

namespace App\Support\Updates;

final class UpdateProductRegistry
{
    /**
     * @return list<string>
     */
    public function enabledProducts(): array
    {
        return collect(config('smelterworks.updates.products', []))
            ->filter(fn (mixed $product): bool => is_array($product) && ($product['enabled'] ?? false))
            ->keys()
            ->map(fn (string $slug): string => $slug)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function product(string $slug): ?array
    {
        if (! $this->pathValidator()->isValidProduct($slug)) {
            return null;
        }

        /** @var array<string, mixed>|null $product */
        $product = config("smelterworks.updates.products.{$slug}");

        if (! is_array($product) || ! ($product['enabled'] ?? false)) {
            return null;
        }

        return $product;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function channel(string $productSlug, string $channelSlug): ?array
    {
        $product = $this->product($productSlug);

        if ($product === null || ! $this->pathValidator()->isValidChannel($channelSlug)) {
            return null;
        }

        /** @var array<string, mixed>|null $channel */
        $channel = data_get($product, "channels.{$channelSlug}");

        if (! is_array($channel) || ! ($channel['enabled'] ?? true)) {
            return null;
        }

        return $channel;
    }

    public function publicBaseUrl(): string
    {
        $configured = config('smelterworks.updates.public_base_url');

        if (filled($configured)) {
            return rtrim((string) $configured, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    public function diskName(): string
    {
        return (string) config('smelterworks.updates.disk', 'local');
    }

    public function maxAssetBytes(): int
    {
        return max(1_048_576, (int) config('smelterworks.updates.max_asset_bytes', 524_288_000));
    }

    public function cacheSeconds(): int
    {
        return max(60, (int) config('smelterworks.updates.cache_seconds', 3600));
    }

    public function staleSeconds(): int
    {
        return max($this->cacheSeconds(), (int) config('smelterworks.updates.stale_seconds', 86400));
    }

    public function warmLockSeconds(): int
    {
        return max(60, (int) config('smelterworks.updates.warm_lock_seconds', 600));
    }

    public function useAccelRedirect(): bool
    {
        return (bool) config('smelterworks.updates.use_accel_redirect', true);
    }

    private function pathValidator(): UpdatePathValidator
    {
        return app(UpdatePathValidator::class);
    }
}
