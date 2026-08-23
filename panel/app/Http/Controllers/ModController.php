<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ModController extends Controller
{
    public function index(GameServer $server, Request $request): View
    {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);

        $query = (string) $request->query('q', '');
        $mods = [];

        if ($query !== '') {
            $response = Http::timeout(10)->get('https://mods.vintagestory.at/api/mods', [
                'text' => $query,
                'limit' => 20,
            ]);

            if ($response->successful()) {
                $mods = $response->json('mods') ?? [];
            }
        }

        return view('servers.mods', [
            'server' => $server,
            'mods' => $mods,
            'query' => $query,
        ]);
    }

    public function install(GameServer $server, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);

        $validated = $request->validate([
            'modid' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:200'],
        ]);

        return back()->with('status', 'Mod install queued for '.$validated['name'].' on '.$server->name.'.');
    }
}
