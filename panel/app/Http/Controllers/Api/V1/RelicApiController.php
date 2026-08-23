<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\GameServer;
use App\Models\MigrationJob;
use App\Support\Relic\RelicConsoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RelicApiController extends Controller
{
    public function servers(Request $request): JsonResponse
    {
        $user = $request->user();
        $organization = $user->organization;

        $servers = $organization->gameServers()
            ->with('daemon')
            ->get()
            ->map(fn (GameServer $server): array => [
                'uuid' => $server->uuid,
                'name' => $server->name,
                'type' => $server->type,
                'status' => $server->status,
                'connect_address' => $server->connect_address,
                'daemon_online' => $server->daemon?->isOnline() ?? false,
            ]);

        return response()->json([
            'schemaVersion' => 1,
            'servers' => $servers,
        ]);
    }

    public function migrations(Request $request): JsonResponse
    {
        $jobs = MigrationJob::query()
            ->where('organization_id', $request->user()->organization_id)
            ->latest()
            ->limit(20)
            ->get(['uuid', 'status', 'bytes', 'completed_at', 'created_at']);

        return response()->json([
            'schemaVersion' => 1,
            'migrations' => $jobs,
        ]);
    }

    public function consoleLogs(GameServer $server, Request $request, RelicConsoleService $console): JsonResponse
    {
        abort_unless($request->user()->organization_id === $server->organization_id, 403);

        $lines = (int) $request->query('lines', 200);
        $output = $console->tail($server, max(10, min($lines, 500)));

        return response()->json([
            'schemaVersion' => 1,
            'server_uuid' => $server->uuid,
            ...$output,
        ]);
    }

    public function createToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'in:relic:read,relic:console'],
        ]);

        $abilities = $validated['abilities'] ?? ['relic:read', 'relic:console'];

        $plain = 'swr_'.Str::random(48);

        ApiToken::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 16),
            'abilities' => $abilities,
        ]);

        return response()->json([
            'token' => $plain,
            'prefix' => substr($plain, 0, 16),
            'abilities' => $abilities,
        ]);
    }
}
