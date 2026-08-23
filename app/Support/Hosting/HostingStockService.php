<?php

namespace App\Support\Hosting;

use App\Models\HostingStock;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HostingStockService
{
    public function __construct(
        private readonly HostingCatalog $catalog,
    ) {}

    /**
     * Ensure stock rows exist from config capacity. Safe to call repeatedly.
     */
    public function syncFromConfig(): void
    {
        /** @var array<string, array<string, int>> $stock */
        $stock = config('smelterworks.hosting.stock', []);

        foreach ($stock as $regionCode => $plans) {
            foreach ($plans as $planSlug => $capacity) {
                if ($this->catalog->isByosPlan($planSlug)) {
                    continue;
                }

                HostingStock::query()->updateOrCreate(
                    [
                        'region_code' => $regionCode,
                        'plan_slug' => $planSlug,
                    ],
                    [
                        'capacity' => (int) $capacity,
                    ],
                );
            }
        }
    }

    /**
     * @return Collection<int, HostingStock>
     */
    public function forPlan(string $planSlug): Collection
    {
        return HostingStock::query()
            ->where('plan_slug', $planSlug)
            ->orderBy('region_code')
            ->get();
    }

    public function remainingForPlan(string $planSlug): int
    {
        return (int) $this->forPlan($planSlug)
            ->sum(fn (HostingStock $stock): int => $stock->remaining());
    }

    public function findLocked(string $regionCode, string $planSlug): HostingStock
    {
        $stock = HostingStock::query()
            ->where('region_code', $regionCode)
            ->where('plan_slug', $planSlug)
            ->lockForUpdate()
            ->first();

        if ($stock === null) {
            throw ValidationException::withMessages([
                'region_code' => 'That plan is not offered in the selected region.',
            ]);
        }

        return $stock;
    }

    /**
     * @return array<string, array{remaining: int, capacity: int, sold: int, by_region: array<string, array{remaining: int, capacity: int, sold: int, label: string}>}>
     */
    public function inventorySnapshot(): array
    {
        $regions = collect($this->catalog->regions())->keyBy('code');
        $snapshot = [];

        foreach ($this->catalog->plans() as $plan) {
            $slug = $plan['slug'];

            if ($this->catalog->isByos($plan)) {
                $snapshot[$slug] = [
                    'remaining' => PHP_INT_MAX,
                    'capacity' => PHP_INT_MAX,
                    'sold' => 0,
                    'by_region' => [],
                    'unlimited' => true,
                ];

                continue;
            }

            $rows = $this->forPlan($slug);
            $byRegion = [];

            foreach ($rows as $row) {
                $byRegion[$row->region_code] = [
                    'remaining' => $row->remaining(),
                    'capacity' => $row->capacity,
                    'sold' => $row->sold,
                    'label' => $regions[$row->region_code]['label'] ?? $row->region_code,
                ];
            }

            $snapshot[$slug] = [
                'remaining' => $rows->sum(fn (HostingStock $stock): int => $stock->remaining()),
                'capacity' => (int) $rows->sum('capacity'),
                'sold' => (int) $rows->sum('sold'),
                'by_region' => $byRegion,
            ];
        }

        return $snapshot;
    }
}
