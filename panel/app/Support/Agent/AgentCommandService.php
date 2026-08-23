<?php

namespace App\Support\Agent;

use App\Models\AgentCommand;
use App\Models\BackupRecord;
use App\Models\DaemonRegistration;
use App\Models\GameServer;
use App\Models\MigrationJob;
use Illuminate\Validation\ValidationException;

final class AgentCommandService
{
    public function dispatch(
        DaemonRegistration $daemon,
        string $type,
        array $payload = [],
        ?GameServer $server = null,
    ): AgentCommand {
        abort_unless($daemon->status === DaemonRegistration::STATUS_ACTIVE, 422, 'Daemon is not active.');

        return AgentCommand::query()->create([
            'daemon_registration_id' => $daemon->id,
            'game_server_id' => $server?->id,
            'type' => $type,
            'status' => AgentCommand::STATUS_PENDING,
            'payload' => $payload,
        ]);
    }

    /**
     * @return list<array{uuid: string, type: string, payload: array<string, mixed>}>
     */
    public function poll(string $daemonUuid, string $fingerprint): array
    {
        $daemon = $this->findActiveDaemon($daemonUuid, $fingerprint);

        $commands = AgentCommand::query()
            ->where('daemon_registration_id', $daemon->id)
            ->where('status', AgentCommand::STATUS_PENDING)
            ->orderBy('id')
            ->limit(5)
            ->get();

        $out = [];

        foreach ($commands as $command) {
            $command->update([
                'status' => AgentCommand::STATUS_DISPATCHED,
                'dispatched_at' => now(),
            ]);

            $out[] = [
                'uuid' => $command->uuid,
                'type' => $command->type,
                'payload' => $command->payload ?? [],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    public function acknowledge(
        string $commandUuid,
        string $daemonUuid,
        string $fingerprint,
        string $status,
        ?array $result = null,
        ?string $error = null,
    ): void {
        if (! in_array($status, [AgentCommand::STATUS_COMPLETED, AgentCommand::STATUS_FAILED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status must be completed or failed.',
            ]);
        }

        $daemon = $this->findActiveDaemon($daemonUuid, $fingerprint);

        $command = AgentCommand::query()
            ->where('uuid', $commandUuid)
            ->where('daemon_registration_id', $daemon->id)
            ->whereIn('status', [AgentCommand::STATUS_PENDING, AgentCommand::STATUS_DISPATCHED])
            ->firstOrFail();

        $command->update([
            'status' => $status,
            'result' => $result,
            'error' => $error,
            'completed_at' => now(),
        ]);

        $this->handleCompletion($command->fresh(['gameServer']));
    }

    public function dispatchForServer(GameServer $server, string $type, array $payload = []): AgentCommand
    {
        $daemon = $server->daemon;

        if ($daemon === null) {
            throw ValidationException::withMessages([
                'daemon' => 'This server has no paired daemon.',
            ]);
        }

        return $this->dispatch($daemon, $type, $payload, $server);
    }

    private function findActiveDaemon(string $daemonUuid, string $fingerprint): DaemonRegistration
    {
        $daemon = DaemonRegistration::query()
            ->where('uuid', $daemonUuid)
            ->where('status', DaemonRegistration::STATUS_ACTIVE)
            ->firstOrFail();

        if (! hash_equals((string) $daemon->fingerprint, $fingerprint)) {
            throw ValidationException::withMessages([
                'fingerprint' => 'Fingerprint mismatch.',
            ]);
        }

        $daemon->update(['last_seen_at' => now()]);

        return $daemon;
    }

    private function handleCompletion(AgentCommand $command): void
    {
        if ($command->status !== AgentCommand::STATUS_COMPLETED) {
            if ($command->type === 'migrate.export') {
                $this->failMigrationJob($command, $command->error ?? 'Export failed.');
            }

            return;
        }

        match ($command->type) {
            'backup.create' => $this->recordBackup($command),
            'migrate.export' => $this->afterMigrateExport($command),
            'migrate.import' => $this->afterMigrateImport($command),
            default => null,
        };
    }

    private function recordBackup(AgentCommand $command): void
    {
        if ($command->game_server_id === null) {
            return;
        }

        BackupRecord::query()->create([
            'game_server_id' => $command->game_server_id,
            'type' => 'local',
            'status' => 'completed',
            'local_path' => $command->result['path'] ?? null,
            'bytes' => $command->result['bytes'] ?? null,
            'checksum' => $command->result['checksum'] ?? null,
            'completed_at' => now(),
        ]);
    }

    private function afterMigrateExport(AgentCommand $command): void
    {
        $jobUuid = $command->payload['job_uuid'] ?? null;

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        $job = MigrationJob::query()->where('uuid', $jobUuid)->first();

        if ($job === null) {
            return;
        }

        $stagingKey = $command->result['staging_key'] ?? $job->staging_key;

        $job->update([
            'status' => 'transferring',
            'staging_key' => is_string($stagingKey) ? $stagingKey : null,
            'bytes' => $command->result['bytes'] ?? $job->bytes,
            'package_hash' => $command->result['package_hash'] ?? $job->package_hash,
            'metadata' => array_merge($job->metadata ?? [], [
                'export_path' => $command->result['path'] ?? null,
            ]),
        ]);

        $destination = $job->destinationServer()->with('daemon')->first();

        if ($destination?->daemon === null) {
            $job->update(['status' => 'failed', 'metadata' => array_merge($job->metadata ?? [], [
                'error' => 'Destination server has no paired daemon.',
            ])]);

            return;
        }

        $this->dispatch($destination->daemon, 'migrate.import', [
            'job_uuid' => $job->uuid,
            'staging_key' => $stagingKey,
            'package_hash' => $job->package_hash,
        ], $destination);
    }

    private function afterMigrateImport(AgentCommand $command): void
    {
        $jobUuid = $command->payload['job_uuid'] ?? null;

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        MigrationJob::query()
            ->where('uuid', $jobUuid)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }

    private function failMigrationJob(AgentCommand $command, string $error): void
    {
        $jobUuid = $command->payload['job_uuid'] ?? null;

        if (! is_string($jobUuid) || $jobUuid === '') {
            return;
        }

        $job = MigrationJob::query()->where('uuid', $jobUuid)->first();

        if ($job === null) {
            return;
        }

        $job->update([
            'status' => 'failed',
            'metadata' => array_merge($job->metadata ?? [], ['error' => $error]),
        ]);
    }
}
