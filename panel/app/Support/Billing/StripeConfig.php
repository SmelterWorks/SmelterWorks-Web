<?php

namespace App\Support\Billing;

final class StripeConfig
{
    public static function enabled(): bool
    {
        return (bool) config('panel.stripe.enabled', false)
            && filled(config('panel.stripe.secret'));
    }
}
