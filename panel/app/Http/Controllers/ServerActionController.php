<?php

namespace App\Http\Controllers;

use App\Models\DaemonRegistration;
use App\Models\GameServer;
use App\Support\Agent\AgentCommandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServerActionController extends Controller
{
    public function linkDaemon(
        GameServer $server,
        Request $request,
    ): RedirectResponse {
        $this->authorizeServer($request, $server);

        $validated = $request->validate([
            'daemon_registration_id' => ['required', 'exists:daemon_registrations,id'],
        ]);

        $daemon = DaemonRegistration::query()->findOrFail($validated['daemon_registration_id']);

        abort_unless($daemon->organization_id === $server->organization_id, 403);
        abort_unless($daemon->status === DaemonRegistration::STATUS_ACTIVE, 422);

        $existing = GameServer::query()
            ->where('daemon_registration_id', $daemon->id)
            ->where('id', '!=', $server->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'daemon_registration_id' => 'This daemon is already linked to another server.',
            ]);
        }

        $server->update([
            'daemon_registration_id' => $daemon->id,
            'status' => $daemon->isOnline() ? 'online' : 'offline',
        ]);

        return back()->with('status', 'Daemon linked to '.$server->name.'.');
    }

    public function power(
        GameServer $server,
        Request $request,
        AgentCommandService $commands,
        string $action,
    ): RedirectResponse {
        $this->authorizeServer($request, $server);

        $type = match ($action) {
            'start' => 'power.start',
            'stop' => 'power.stop',
            'restart' => 'power.restart',
            default => throw ValidationException::withMessages(['action' => 'Invalid power action.']),
        };

        $commands->dispatchForServer($server, $type);

        return back()->with('status', ucfirst($action).' command queued.');
    }

    public function backup(
        GameServer $server,
        Request $request,
        AgentCommandService $commands,
    ): RedirectResponse {
        $this->authorizeServer($request, $server);

        $commands->dispatchForServer($server, 'backup.create');

        return back()->with('status', 'Backup command queued.');
    }

    private function authorizeServer(Request $request, GameServer $server): void
    {
        abort_unless(
            $request->user()?->organization_id === $server->organization_id,
            403,
        );
    }
}
