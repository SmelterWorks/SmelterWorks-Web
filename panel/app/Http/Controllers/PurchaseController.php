<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Support\Billing\StripeConfig;
use App\Support\Billing\StripeService;
use App\Support\Hosting\ManagedProvisioningService;
use App\Support\Permissions\OrganizationAccess;
use App\Support\Permissions\SubuserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(ManagedProvisioningService $plans): View
    {
        return view('servers.purchase', [
            'plans' => collect(config('panel.plans', []))->map(fn (array $plan, string $slug): array => array_merge($plan, ['slug' => $slug])),
            'regions' => config('panel.regions', []),
            'stripeEnabled' => StripeConfig::enabled(),
            'managedMode' => config('panel.mode') === 'managed',
        ]);
    }

    public function store(
        Request $request,
        ManagedProvisioningService $plans,
        StripeService $stripe,
        OrganizationAccess $access,
    ): RedirectResponse {
        $access->authorize($request, SubuserPermission::MANAGE_BILLING);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'plan_slug' => ['required', 'string'],
            'region_code' => ['required', 'string'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'type' => ['required', 'in:managed,byos'],
        ]);

        $plan = $plans->plan($validated['plan_slug']);
        $organization = $request->user()->organization;

        if ($validated['type'] === 'managed' && config('panel.mode') !== 'managed') {
            return back()->withErrors(['type' => 'Managed hosting is not available on this panel instance.']);
        }

        if ($validated['type'] === 'byos') {
            GameServer::query()->create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'type' => 'byos',
                'plan_slug' => $validated['plan_slug'],
                'billing_cycle' => $validated['billing_cycle'],
                'region_code' => $validated['region_code'],
                'status' => 'pending',
                'ram_gb' => $plan['ram_gb'] ?? 0,
                'storage_gb' => $plan['storage_gb'] ?? 0,
            ]);

            return redirect()->route('dashboard')->with('status', 'BYOS server created. Pair a daemon to get started.');
        }

        if (! StripeConfig::enabled()) {
            return back()->withErrors(['billing' => 'Stripe billing is not enabled.']);
        }

        $server = GameServer::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'type' => 'managed',
            'plan_slug' => $validated['plan_slug'],
            'billing_cycle' => $validated['billing_cycle'],
            'region_code' => $validated['region_code'],
            'status' => 'pending_payment',
            'ram_gb' => $plan['ram_gb'],
            'storage_gb' => $plan['storage_gb'],
        ]);

        $amount = $validated['billing_cycle'] === 'yearly'
            ? (int) $plan['price_yearly'] * 100
            : (int) $plan['price_monthly'] * 100;

        $session = $stripe->createSubscriptionCheckout($organization, [
            'name' => $validated['name'].' ('.$validated['plan_slug'].')',
            'amount_cents' => $amount,
            'interval' => $validated['billing_cycle'] === 'yearly' ? 'year' : 'month',
            'server_uuid' => $server->uuid,
        ]);

        return redirect()->away($session->url);
    }
}
