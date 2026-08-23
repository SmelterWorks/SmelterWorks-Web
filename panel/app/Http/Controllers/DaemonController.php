<?php

namespace App\Http\Controllers;

use App\Models\DaemonRegistration;
use App\Support\Agent\AgentHandshakeService;
use App\Support\Agent\HubKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DaemonController extends Controller
{
    public function create(Request $request, HubKeyService $keys, AgentHandshakeService $handshake): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $pair = $keys->generate();

        $daemon = DaemonRegistration::query()->create([
            'organization_id' => $user->organization_id,
            'name' => $validated['name'],
            'token_hash' => hash('sha256', 'pending'),
            'token_prefix' => 'pending',
            'hub_public_key' => $pair['public_key'],
            'hub_private_key' => $pair['private_key'],
            'status' => DaemonRegistration::STATUS_PENDING,
        ]);

        $issued = $handshake->issueToken($daemon);

        return back()->with([
            'status' => 'Daemon registration created. Copy the token now. It will not be shown again.',
            'daemon_token' => $issued['token'],
            'daemon_uuid' => $daemon->uuid,
            'hub_public_key' => $issued['hub_public_key'],
        ]);
    }

    public function pairing(Request $request): View
    {
        return view('daemons.pairing', [
            'daemons' => $request->user()->organization?->daemons()->latest()->get() ?? collect(),
        ]);
    }
}
