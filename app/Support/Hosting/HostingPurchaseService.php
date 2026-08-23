<?php

namespace App\Support\Hosting;

use App\Models\HostingPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostingPurchaseService
{
    public function __construct(
        private readonly HostingCatalog $catalog,
        private readonly HostingStockService $stock,
    ) {}

    /**
     * @param  array{plan_slug: string, region_code: string, billing_cycle: string, customer_name: string, customer_email: string, server_name?: string|null}  $input
     */
    public function purchase(array $input): HostingPurchase
    {
        $plan = $this->catalog->plan($input['plan_slug']);
        $this->catalog->regionLabel($input['region_code']);

        $billingCycle = $input['billing_cycle'];

        if (! in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw ValidationException::withMessages([
                'billing_cycle' => 'Choose monthly or yearly billing.',
            ]);
        }

        $amount = $billingCycle === 'yearly'
            ? (int) $plan['price_yearly']
            : (int) $plan['price_monthly'];

        return DB::transaction(function () use ($input, $amount, $billingCycle, $plan): HostingPurchase {
            if (! $this->catalog->isByos($plan)) {
                $stock = $this->stock->findLocked($input['region_code'], $input['plan_slug']);

                if (! $stock->isAvailable()) {
                    throw ValidationException::withMessages([
                        'region_code' => 'That plan is sold out in this region. Pick the other region or another plan.',
                    ]);
                }

                $stock->increment('sold');
            }

            return HostingPurchase::query()->create([
                'plan_slug' => $input['plan_slug'],
                'region_code' => $input['region_code'],
                'billing_cycle' => $billingCycle,
                'amount_usd' => $amount,
                'customer_name' => $input['customer_name'],
                'customer_email' => $input['customer_email'],
                'server_name' => $input['server_name'] ?? null,
                'status' => HostingPurchase::STATUS_PENDING,
            ]);
        });
    }

    public function markPaid(HostingPurchase $purchase): HostingPurchase
    {
        if ($purchase->status === HostingPurchase::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'purchase' => 'Cancelled purchases cannot be marked paid.',
            ]);
        }

        $purchase->fill([
            'status' => HostingPurchase::STATUS_PAID,
            'paid_at' => now(),
        ])->save();

        return $purchase->refresh();
    }

    public function cancel(HostingPurchase $purchase): HostingPurchase
    {
        if ($purchase->status === HostingPurchase::STATUS_CANCELLED) {
            return $purchase;
        }

        return DB::transaction(function () use ($purchase): HostingPurchase {
            $locked = HostingPurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === HostingPurchase::STATUS_CANCELLED) {
                return $locked;
            }

            if ($locked->holdsStock() && ! $this->catalog->isByosPlan($locked->plan_slug)) {
                $stock = $this->stock->findLocked($locked->region_code, $locked->plan_slug);

                if ($stock->sold > 0) {
                    $stock->decrement('sold');
                }
            }

            $locked->fill([
                'status' => HostingPurchase::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }
}
