<?php

namespace App\Http\Controllers;

use App\Support\Billing\StripeConfig;
use App\Support\Billing\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): Response
    {
        if (! StripeConfig::enabled()) {
            return response('billing disabled', 503);
        }

        $stripe->handleWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature'),
        );

        return response('ok', 200);
    }
}
