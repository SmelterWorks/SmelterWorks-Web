<?php

namespace Tests\Feature;

use App\Models\AgentCommand;
use App\Models\DaemonRegistration;
use App\Models\GameServer;
use App\Models\Organization;
use App\Models\User;
use App\Support\Agent\AgentCommandService;
use App\Support\Agent\HubKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_daemon_polls_and_acknowledges_backup_command(): void
    {
        [$daemon, $server] = $this->pairedDaemonAndServer();

        $command = app(AgentCommandService::class)->dispatchForServer($server, 'backup.create');

        $poll = $this->postJson('/api/v1/agent/poll', [
            'daemon_uuid' => $daemon->uuid,
            'fingerprint' => 'fp-test-123',
        ])->assertOk();

        $poll->assertJsonPath('commands.0.uuid', $command->uuid);
        $poll->assertJsonPath('commands.0.type', 'backup.create');

        $this->postJson('/api/v1/agent/ack', [
            'daemon_uuid' => $daemon->uuid,
            'fingerprint' => 'fp-test-123',
            'command_uuid' => $command->uuid,
            'status' => 'completed',
            'result' => ['path' => '/backups/test.tar.gz', 'bytes' => 1024],
        ])->assertOk();

        $command->refresh();
        $this->assertSame(AgentCommand::STATUS_COMPLETED, $command->status);
        $this->assertDatabaseHas('backup_records', [
            'game_server_id' => $server->id,
            'status' => 'completed',
        ]);
    }

    public function test_server_power_action_queues_command(): void
    {
        [$daemon, $server] = $this->pairedDaemonAndServer();
        $user = User::factory()->create([
            'organization_id' => $server->organization_id,
        ]);

        $this->actingAs($user)
            ->post(route('servers.power', [$server, 'start']))
            ->assertRedirect();

        $this->assertDatabaseHas('agent_commands', [
            'daemon_registration_id' => $daemon->id,
            'game_server_id' => $server->id,
            'type' => 'power.start',
            'status' => AgentCommand::STATUS_PENDING,
        ]);
    }

    /**
     * @return array{0: DaemonRegistration, 1: GameServer}
     */
    private function pairedDaemonAndServer(): array
    {
        $org = Organization::query()->create(['name' => 'Test Org', 'slug' => 'test-org']);
        $keys = app(HubKeyService::class)->generate();
        $daemon = DaemonRegistration::query()->create([
            'organization_id' => $org->id,
            'name' => 'home-1',
            'token_hash' => hash('sha256', 'pending'),
            'token_prefix' => 'pending',
            'hub_public_key' => $keys['public_key'],
            'hub_private_key' => $keys['private_key'],
            'status' => DaemonRegistration::STATUS_ACTIVE,
            'fingerprint' => 'fp-test-123',
            'last_seen_at' => now(),
        ]);

        $server = GameServer::query()->create([
            'organization_id' => $org->id,
            'daemon_registration_id' => $daemon->id,
            'name' => 'Test Server',
            'type' => GameServer::TYPE_BYOS,
            'status' => 'online',
        ]);

        return [$daemon, $server];
    }
}
