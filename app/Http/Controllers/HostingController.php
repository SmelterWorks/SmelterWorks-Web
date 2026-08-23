<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHostingPurchaseRequest;
use App\Models\HostingPurchase;
use App\Support\Currency\ExchangeRateService;
use App\Support\Hosting\HostingCatalog;
use App\Support\Hosting\HostingIndexPresenter;
use App\Support\Hosting\HostingPurchaseService;
use App\Support\Hosting\HostingStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HostingController extends Controller
{
    public function __construct(
        private readonly HostingCatalog $catalog,
        private readonly HostingStockService $stock,
        private readonly HostingPurchaseService $purchases,
        private readonly HostingIndexPresenter $index,
        private readonly ExchangeRateService $exchange,
    ) {}

    public function index(): View
    {
        return view('pages.hosting', $this->index->present());
    }

    public function create(string $plan): View|RedirectResponse
    {
        if ($this->purchasesClosed()) {
            return $this->comingSoonRedirect();
        }

        $this->stock->syncFromConfig();

        $selected = $this->catalog->plan($plan);
        $isByos = $this->catalog->isByos($selected);
        $stocks = $isByos ? collect() : $this->stock->forPlan($plan);
        $quote = $this->exchange->quote();

        return view('pages.hosting.purchase', [
            'plan' => $selected,
            'isByos' => $isByos,
            'regions' => $isByos
                ? [['code' => $selected['region_code'], 'label' => $selected['region_label'] ?? 'Your hardware']]
                : $this->catalog->regions(),
            'stocks' => $stocks,
            'remaining' => $isByos ? PHP_INT_MAX : $this->stock->remainingForPlan($plan),
            'cloudBackupTiers' => $this->catalog->cloudBackupTiers(),
            'exchange' => $quote,
            'priceMonthlyEur' => $quote['available']
                ? round($selected['price_monthly'] * $quote['rate'], 2)
                : null,
            'priceYearlyEur' => $quote['available']
                ? round($selected['price_yearly'] * $quote['rate'], 2)
                : null,
        ]);
    }

    public function store(StoreHostingPurchaseRequest $request): RedirectResponse
    {
        if ($this->purchasesClosed()) {
            return $this->comingSoonRedirect();
        }

        $this->stock->syncFromConfig();

        $purchase = $this->purchases->purchase($request->validated());

        return redirect()
            ->route('hosting.purchases.show', $purchase)
            ->with('status', 'Order reserved. Stock is held while payment is completed.');
    }

    public function show(HostingPurchase $purchase): View|RedirectResponse
    {
        if ($this->purchasesClosed()) {
            return $this->comingSoonRedirect();
        }

        $plan = $this->catalog->plan($purchase->plan_slug);

        return view('pages.hosting.purchase-show', [
            'purchase' => $purchase,
            'plan' => $plan,
            'regionLabel' => $this->catalog->regionLabel($purchase->region_code),
        ]);
    }

    private function purchasesClosed(): bool
    {
        return (bool) config('smelterworks.hosting.coming_soon', false);
    }

    private function comingSoonRedirect(): RedirectResponse
    {
        return redirect()
            ->route('hosting')
            ->with('status', 'Hosting purchases are coming soon.');
    }
}
