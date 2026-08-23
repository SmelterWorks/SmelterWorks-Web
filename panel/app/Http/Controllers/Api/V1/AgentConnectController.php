<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Agent\AgentCommandService;
use App\Support\Agent\AgentHandshakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentConnectController extends Controller
{
    public function connect(Request $request, AgentHandshakeService $handshake): JsonResponse
    {
        $token = (string) $request->header('X-Agent-Token', $request->input('token', ''));

        if ($token === '') {
            return response()->json(['error' => 'missing_token'], 401);
        }

        $begin = $handshake->begin($token);

        return response()->json([
            'schemaVersion' => 1,
            'step' => 'hub_challenge',
            'token' => $token,
            'signature' => $begin['signature'],
            'challenge' => $begin['challenge'],
        ]);
    }

    public function complete(Request $request, AgentHandshakeService $handshake): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'fingerprint' => ['required', 'string', 'max:128'],
            'challenge' => ['required', 'string'],
        ]);

        $result = $handshake->complete(
            $validated['token'],
            $validated['fingerprint'],
            $validated['challenge'],
        );

        return response()->json([
            'schemaVersion' => 1,
            'step' => 'authorized',
            ...$result,
        ]);
    }

    public function heartbeat(Request $request, AgentHandshakeService $handshake): JsonResponse
    {
        $validated = $request->validate([
            'daemon_uuid' => ['required', 'uuid'],
            'fingerprint' => ['required', 'string', 'max:128'],
            'container_status' => ['nullable', 'string', 'max:64'],
        ]);

        $handshake->heartbeat(
            $validated['daemon_uuid'],
            $validated['fingerprint'],
            $validated['container_status'] ?? null,
        );

        return response()->json(['status' => 'ok']);
    }

    public function poll(Request $request, AgentCommandService $commands): JsonResponse
    {
        $validated = $request->validate([
            'daemon_uuid' => ['required', 'uuid'],
            'fingerprint' => ['required', 'string', 'max:128'],
        ]);

        return response()->json([
            'schemaVersion' => 1,
            'commands' => $commands->poll($validated['daemon_uuid'], $validated['fingerprint']),
        ]);
    }

    public function acknowledge(Request $request, AgentCommandService $commands): JsonResponse
    {
        $validated = $request->validate([
            'daemon_uuid' => ['required', 'uuid'],
            'fingerprint' => ['required', 'string', 'max:128'],
            'command_uuid' => ['required', 'uuid'],
            'status' => ['required', 'in:completed,failed'],
            'result' => ['nullable', 'array'],
            'error' => ['nullable', 'string', 'max:2000'],
        ]);

        $commands->acknowledge(
            $validated['command_uuid'],
            $validated['daemon_uuid'],
            $validated['fingerprint'],
            $validated['status'],
            $validated['result'] ?? null,
            $validated['error'] ?? null,
        );

        return response()->json(['status' => 'ok']);
    }
}
