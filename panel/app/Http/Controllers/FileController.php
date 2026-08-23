<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Support\Agent\AgentCommandService;
use App\Support\Permissions\OrganizationAccess;
use App\Support\Permissions\SubuserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FileController extends Controller
{
    public function index(GameServer $server, Request $request, OrganizationAccess $access): View
    {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);
        $access->authorize($request, SubuserPermission::MANAGE_FILES);

        return view('servers.files', [
            'server' => $server,
            'path' => (string) $request->query('path', ''),
        ]);
    }

    public function list(GameServer $server, Request $request, AgentCommandService $commands, OrganizationAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);
        $access->authorize($request, SubuserPermission::MANAGE_FILES);

        $validated = $request->validate([
            'path' => ['nullable', 'string', 'max:500'],
        ]);

        $commands->dispatchForServer($server, 'files.list', [
            'path' => $validated['path'] ?? '',
        ]);

        return back()->with('status', 'File list refresh queued.');
    }

    public function upload(GameServer $server, Request $request, AgentCommandService $commands, OrganizationAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->organization_id === $server->organization_id, 403);
        $access->authorize($request, SubuserPermission::MANAGE_FILES);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $commands->dispatchForServer($server, 'files.write', [
            'path' => $validated['path'],
            'content_base64' => base64_encode($validated['content']),
        ]);

        return back()->with('status', 'File save queued.');
    }
}
