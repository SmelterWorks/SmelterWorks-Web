<?php

namespace App\Support\Hosting;

use App\Support\Currency\ExchangeRateService;

final class HostingIndexPresenter
{
    public function __construct(
        private readonly HostingCatalog $catalog,
        private readonly HostingStockService $stock,
        private readonly ExchangeRateService $exchange,
    ) {}

    /**
     * @return array{hosting: array<string, mixed>, exchange: array<string, mixed>, comingSoon: bool}
     */
    public function present(): array
    {
        $comingSoon = (bool) config('smelterworks.hosting.coming_soon', false);
        $quote = $this->exchange->quote();

        $inventory = [];

        if (! $comingSoon) {
            $this->stock->syncFromConfig();
            $inventory = $this->stock->inventorySnapshot();
        }

        $plans = collect($this->catalog->plans())
            ->map(function (array $plan) use ($quote, $inventory, $comingSoon): array {
                $plan['price_monthly_eur'] = $quote['available']
                    ? round($plan['price_monthly'] * $quote['rate'], 2)
                    : null;
                $plan['price_yearly_eur'] = $quote['available']
                    ? round($plan['price_yearly'] * $quote['rate'], 2)
                    : null;
                $plan['yearly_savings_eur'] = $quote['available']
                    ? round($plan['yearly_savings'] * $quote['rate'], 2)
                    : null;
                $plan['coming_soon'] = $comingSoon;
                $plan['stock'] = $inventory[$plan['slug']] ?? [
                    'remaining' => 0,
                    'capacity' => 0,
                    'sold' => 0,
                    'by_region' => [],
                ];

                return $plan;
            })
            ->all();

        $hosting = config('smelterworks.hosting');
        $hosting['plans'] = $plans;

        return [
            'hosting' => $hosting,
            'exchange' => $quote,
            'comingSoon' => $comingSoon,
        ];
    }
}
