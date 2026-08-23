<?php

namespace App\Support\Billing;

use App\Models\GameServer;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Webhook;

final class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey((string) config('panel.stripe.secret'));
    }

    public function ensureCustomer(Organization $organization): string
    {
        if ($organization->stripe_customer_id !== null) {
            return $organization->stripe_customer_id;
        }

        $customer = Customer::create([
            'email' => $organization->billing_email ?? $organization->users()->value('email'),
            'name' => $organization->name,
            'metadata' => [
                'organization_id' => (string) $organization->id,
            ],
        ]);

        $organization->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * @param  array{name: string, amount_cents: int, interval: string, server_uuid: string}  $line
     */
    public function createSubscriptionCheckout(Organization $organization, array $line): Session
    {
        $customerId = $this->ensureCustomer($organization);

        return Session::create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('dashboard'),
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $line['amount_cents'],
                    'recurring' => ['interval' => $line['interval']],
                    'product_data' => ['name' => $line['name']],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'server_uuid' => $line['server_uuid'],
                'organization_id' => (string) $organization->id,
            ],
        ]);
    }

    public function portalUrl(Organization $organization): string
    {
        $customerId = $this->ensureCustomer($organization);
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customerId,
            'return_url' => route('dashboard'),
        ]);

        return $session->url;
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        $secret = (string) config('panel.stripe.webhook_secret');

        if ($secret === '') {
            Log::warning('Stripe webhook received but PANEL_STRIPE_WEBHOOK_SECRET is empty.');

            return;
        }

        $event = Webhook::constructEvent($payload, (string) $signature, $secret);

        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;
            $serverUuid = $session->metadata['server_uuid'] ?? null;
            $subscriptionId = is_string($session->subscription) ? $session->subscription : null;

            if (is_string($serverUuid) && $subscriptionId !== null) {
                GameServer::query()
                    ->where('uuid', $serverUuid)
                    ->update(['stripe_subscription_id' => $subscriptionId]);
            }
        }
    }
}
