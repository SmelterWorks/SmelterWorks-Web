<?php

namespace App\Support\Billing;

final class StripeConfig
{
    public static function enabled(): bool
    {
        return (bool) config('services.stripe.enabled', false)
            && filled(config('services.stripe.secret'));
    }
}
