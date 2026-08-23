<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function show(GameServer $server, Request $request): View
    {
        $this->authorizeServer($request, $server);

        return view('servers.show', [
            'server' => $server->load('daemon', 'backups'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->organization;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:managed,byos'],
        ]);

        GameServer::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Server created.');
    }

    private function authorizeServer(Request $request, GameServer $server): void
    {
        abort_unless(
            $request->user()?->organization_id === $server->organization_id,
            403,
        );
    }
}
