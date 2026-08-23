<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\MigrationJob;
use App\Support\Agent\AgentCommandService;
use App\Support\Migration\MigrationRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MigrationController extends Controller
{
    public function store(
        GameServer $server,
        Request $request,
        MigrationRateLimiter $limiter,
        AgentCommandService $commands,
    ): RedirectResponse {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);

        $validated = $request->validate([
            'destination_server_id' => ['required', 'exists:game_servers,id'],
        ]);

        $destination = GameServer::query()->findOrFail($validated['destination_server_id']);
        abort_unless($destination->organization_id === $server->organization_id, 403);

        $limiter->ensureAllowed($server->organization_id);

        $job = MigrationJob::query()->create([
            'organization_id' => $server->organization_id,
            'source_server_id' => $server->id,
            'destination_server_id' => $destination->id,
            'status' => 'pending',
            'staging_key' => 'migrations/'.Str::uuid().'.tar.gz',
            'staging_expires_at' => now()->addHours((int) config('panel.migration.staging_ttl_hours', 6)),
            'metadata' => [
                'initiated_by' => $request->user()->id,
            ],
        ]);

        $commands->dispatchForServer($server, 'migrate.export', [
            'job_uuid' => $job->uuid,
            'staging_key' => $job->staging_key,
            'server_uuid' => $server->uuid,
        ]);

        $job->update(['status' => 'packaging']);

        return back()->with('status', 'Migration queued. Source server will freeze when export starts.');
    }
}
