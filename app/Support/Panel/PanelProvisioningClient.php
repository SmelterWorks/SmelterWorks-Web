<?php

namespace App\Support\Panel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PanelProvisioningClient
{
    /**
     * @param  array{plan_slug: string, region_code: string, billing_cycle: string, server_name: string, customer_email: string, customer_name: string, purchase_uuid: string}  $payload
     * @return array{server_uuid?: string, status?: string}|null
     */
    public function provision(array $payload): ?array
    {
        $url = (string) config('services.panel.provision_url');
        $secret = (string) config('services.panel.provision_secret');

        if ($url === '' || $secret === '') {
            Log::warning('Panel provisioning skipped because PANEL_PROVISION_URL or PANEL_PROVISION_SECRET is empty.');

            return null;
        }

        $response = Http::timeout(30)
            ->withHeaders(['X-Provision-Secret' => $secret])
            ->post(rtrim($url, '/').'/api/v1/provision', $payload);

        if (! $response->successful()) {
            Log::error('Panel provisioning failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }
}
