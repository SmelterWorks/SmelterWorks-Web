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
     * @return array{hosting: array<string, mixed>, byosPlan: array<string, mixed>|null, managedPlans: list<array<string, mixed>>, exchange: array<string, mixed>, comingSoon: bool}
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

                if ($this->catalog->isByos($plan)) {
                    $plan['stock'] = [
                        'remaining' => PHP_INT_MAX,
                        'capacity' => PHP_INT_MAX,
                        'sold' => 0,
                        'by_region' => [],
                        'unlimited' => true,
                    ];
                } else {
                    $plan['stock'] = $inventory[$plan['slug']] ?? [
                        'remaining' => 0,
                        'capacity' => 0,
                        'sold' => 0,
                        'by_region' => [],
                    ];
                }

                return $plan;
            })
            ->all();

        $hosting = config('smelterworks.hosting');
        $hosting['plans'] = $plans;
        $hosting['cloud_backup_tiers'] = $this->catalog->cloudBackupTiers();

        $planCollection = collect($plans);

        return [
            'hosting' => $hosting,
            'byosPlan' => $planCollection->first(fn (array $plan): bool => $this->catalog->isByos($plan)),
            'managedPlans' => $planCollection->reject(fn (array $plan): bool => $this->catalog->isByos($plan))->values()->all(),
            'exchange' => $quote,
            'comingSoon' => $comingSoon,
        ];
    }
}
