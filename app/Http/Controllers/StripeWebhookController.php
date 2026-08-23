<?php

namespace App\Http\Controllers;

use App\Models\HostingPurchase;
use App\Support\Billing\StripeCheckoutService;
use App\Support\Hosting\HostingCatalog;
use App\Support\Hosting\HostingPurchaseService;
use App\Support\Panel\PanelProvisioningClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        HostingPurchaseService $purchases,
        HostingCatalog $catalog,
        PanelProvisioningClient $panel,
    ): Response {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            return response('webhook disabled', 503);
        }

        $event = Webhook::constructEvent(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
            $secret,
        );

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $purchaseUuid = $session->metadata['purchase_uuid'] ?? null;

            if (! is_string($purchaseUuid) || $purchaseUuid === '') {
                return response('ok', 200);
            }

            $purchase = HostingPurchase::query()->where('uuid', $purchaseUuid)->first();

            if ($purchase === null || $purchase->status === HostingPurchase::STATUS_PAID) {
                return response('ok', 200);
            }

            $purchase->update([
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : null,
            ]);

            $purchases->markPaid($purchase);

            if (! $catalog->isByosPlan($purchase->plan_slug)) {
                $result = $panel->provision([
                    'plan_slug' => $purchase->plan_slug,
                    'region_code' => $purchase->region_code,
                    'billing_cycle' => $purchase->billing_cycle,
                    'server_name' => $purchase->server_name ?? $purchase->customer_name,
                    'customer_email' => $purchase->customer_email,
                    'customer_name' => $purchase->customer_name,
                    'purchase_uuid' => $purchase->uuid,
                ]);

                if (is_array($result) && isset($result['server_uuid'])) {
                    $purchase->update(['provisioned_server_uuid' => $result['server_uuid']]);
                }
            }
        }

        return response('ok', 200);
    }
}
