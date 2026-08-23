<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\MigrationJob;
use App\Support\Migration\MigrationRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    public function store(
        GameServer $server,
        Request $request,
        MigrationRateLimiter $limiter,
    ): RedirectResponse {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);

        $validated = $request->validate([
            'destination_server_id' => ['required', 'exists:game_servers,id'],
        ]);

        $destination = GameServer::query()->findOrFail($validated['destination_server_id']);
        abort_unless($destination->organization_id === $server->organization_id, 403);

        $limiter->ensureAllowed($server->organization_id);

        MigrationJob::query()->create([
            'organization_id' => $server->organization_id,
            'source_server_id' => $server->id,
            'destination_server_id' => $destination->id,
            'status' => 'pending',
            'staging_expires_at' => now()->addHours((int) config('panel.migration.staging_ttl_hours', 6)),
            'metadata' => [
                'initiated_by' => $request->user()->id,
            ],
        ]);

        return back()->with('status', 'Migration queued. Source server will freeze when the job starts.');
    }
}
