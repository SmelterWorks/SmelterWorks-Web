<?php

namespace App\Support\Relic;

use App\Models\AgentCommand;
use App\Models\GameServer;
use App\Support\Agent\AgentCommandService;
use Illuminate\Validation\ValidationException;

final class RelicConsoleService
{
    public function __construct(
        private readonly AgentCommandService $commands,
    ) {}

    /**
     * @return array{lines: list<string>, truncated: bool}
     */
    public function tail(GameServer $server, int $lines = 200): array
    {
        $command = $this->commands->dispatchForServer($server, 'console.logs', [
            'lines' => $lines,
        ]);

        $deadline = now()->addSeconds(15);

        while (now()->lt($deadline)) {
            $command->refresh();

            if ($command->status === AgentCommand::STATUS_COMPLETED) {
                return [
                    'lines' => $command->result['lines'] ?? [],
                    'truncated' => (bool) ($command->result['truncated'] ?? false),
                ];
            }

            if ($command->status === AgentCommand::STATUS_FAILED) {
                throw ValidationException::withMessages([
                    'console' => $command->error ?? 'Console fetch failed.',
                ]);
            }

            usleep(500_000);
        }

        throw ValidationException::withMessages([
            'console' => 'Timed out waiting for console output.',
        ]);
    }
}
