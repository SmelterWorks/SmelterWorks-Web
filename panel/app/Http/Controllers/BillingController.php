<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Support\Billing\StripeService;
use App\Support\Hosting\ManagedProvisioningService;
use App\Support\Permissions\OrganizationAccess;
use App\Support\Permissions\SubuserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function portal(Request $request, StripeService $stripe, OrganizationAccess $access): RedirectResponse
    {
        $access->authorize($request, SubuserPermission::MANAGE_BILLING);
        $organization = $request->user()->organization;

        return redirect()->away($stripe->portalUrl($organization));
    }

    public function subscribe(
        GameServer $server,
        Request $request,
        StripeService $stripe,
        ManagedProvisioningService $plans,
        OrganizationAccess $access,
    ): RedirectResponse {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);
        $access->authorize($request, SubuserPermission::MANAGE_BILLING);

        if ($server->plan_slug === null) {
            return back()->withErrors(['billing' => 'This server has no billable plan.']);
        }

        $plan = $plans->plan($server->plan_slug);
        $interval = $server->billing_cycle === 'yearly' ? 'year' : 'month';
        $amount = $server->billing_cycle === 'yearly'
            ? (int) $plan['price_yearly'] * 100
            : (int) $plan['price_monthly'] * 100;

        $session = $stripe->createSubscriptionCheckout($server->organization, [
            'name' => $server->name.' ('.$server->plan_slug.')',
            'amount_cents' => $amount,
            'interval' => $interval,
            'server_uuid' => $server->uuid,
        ]);

        return redirect()->away($session->url);
    }

    public function success(): View
    {
        return view('billing.success');
    }
}
