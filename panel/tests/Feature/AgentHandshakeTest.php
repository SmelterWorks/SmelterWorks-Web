<?php

namespace Tests\Feature;

use App\Models\DaemonRegistration;
use App\Models\Organization;
use App\Support\Agent\AgentHandshakeService;
use App\Support\Agent\HubKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentHandshakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_handshake_authorizes_daemon(): void
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
            'status' => DaemonRegistration::STATUS_PENDING,
        ]);

        $handshake = app(AgentHandshakeService::class);
        $issued = $handshake->issueToken($daemon);
        $begin = $handshake->begin($issued['token']);

        $this->postJson('/api/v1/agent/complete', [
            'token' => $issued['token'],
            'fingerprint' => 'fp-test-123',
            'challenge' => $begin['challenge'],
        ])->assertOk()->assertJson([
            'status' => 'authorized',
        ]);

        $daemon->refresh();
        $this->assertSame('active', $daemon->status);
        $this->assertSame('fp-test-123', $daemon->fingerprint);
    }
}
