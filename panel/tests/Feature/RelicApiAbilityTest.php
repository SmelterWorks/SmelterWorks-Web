<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\DaemonRegistration;
use App\Models\GameServer;
use App\Models\Organization;
use App\Models\User;
use App\Support\Agent\HubKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelicApiAbilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_endpoint_requires_console_ability(): void
    {
        [$server, $token] = $this->serverWithToken(['relic:read']);

        $this->withToken($token)
            ->getJson('/api/v1/relic/servers/'.$server->uuid.'/console/logs')
            ->assertForbidden()
            ->assertJson(['error' => 'missing_ability']);
    }

    public function test_servers_endpoint_works_with_read_ability(): void
    {
        [$server, $token] = $this->serverWithToken(['relic:read']);

        $this->withToken($token)
            ->getJson('/api/v1/relic/servers')
            ->assertOk()
            ->assertJsonPath('servers.0.uuid', $server->uuid);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{0: GameServer, 1: string}
     */
    private function serverWithToken(array $abilities): array
    {
        $org = Organization::query()->create(['name' => 'Org', 'slug' => 'org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $keys = app(HubKeyService::class)->generate();
        $daemon = DaemonRegistration::query()->create([
            'organization_id' => $org->id,
            'name' => 'node',
            'token_hash' => hash('sha256', 'pending'),
            'token_prefix' => 'pending',
            'hub_public_key' => $keys['public_key'],
            'hub_private_key' => $keys['private_key'],
            'status' => DaemonRegistration::STATUS_ACTIVE,
            'fingerprint' => 'fp',
            'last_seen_at' => now(),
        ]);
        $server = GameServer::query()->create([
            'organization_id' => $org->id,
            'daemon_registration_id' => $daemon->id,
            'name' => 'Srv',
            'type' => GameServer::TYPE_BYOS,
            'status' => 'online',
        ]);

        $plain = 'swr_test_ability_token_123456789012345678901234';
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'relic',
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 16),
            'abilities' => $abilities,
        ]);

        return [$server, $plain];
    }
}
