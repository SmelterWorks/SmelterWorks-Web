<?php

namespace App\Support\Billing;

use App\Models\HostingPurchase;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

final class StripeCheckoutService
{
    public function __construct()
    {
        if (! StripeConfig::enabled()) {
            return;
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));
    }

    public function createCheckoutSession(HostingPurchase $purchase, string $planName): Session
    {
        if (! StripeConfig::enabled()) {
            throw ValidationException::withMessages([
                'billing' => 'Stripe checkout is not configured.',
            ]);
        }

        return Session::create([
            'mode' => 'payment',
            'customer_email' => $purchase->customer_email,
            'success_url' => route('hosting.purchases.show', $purchase).'?paid=1',
            'cancel_url' => route('hosting.purchases.show', $purchase),
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $purchase->amount_usd * 100,
                    'product_data' => [
                        'name' => $planName.' hosting ('.$purchase->billing_cycle.')',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'purchase_uuid' => $purchase->uuid,
            ],
        ]);
    }
}
