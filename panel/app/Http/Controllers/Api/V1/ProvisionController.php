<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Hosting\ManagedProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvisionController extends Controller
{
    public function store(Request $request, ManagedProvisioningService $provisioning): JsonResponse
    {
        $secret = (string) config('panel.provision.secret');
        $provided = (string) $request->header('X-Provision-Secret', '');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'max:32'],
            'region_code' => ['required', 'string', 'max:32'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'server_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['required', 'string', 'max:120'],
            'purchase_uuid' => ['nullable', 'uuid'],
        ]);

        $server = $provisioning->provision($validated);

        return response()->json([
            'schemaVersion' => 1,
            'server_uuid' => $server->uuid,
            'status' => $server->status,
        ], 201);
    }
}
