<?php

namespace Tests\Feature;

use App\Models\DaemonRegistration;
use App\Models\GameServer;
use App\Models\HostNode;
use App\Models\Organization;
use App\Support\Agent\HubKeyService;
use App\Support\Hosting\ManagedProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ManagedProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_provision_creates_server_on_host_node(): void
    {
        Config::set('panel.mode', 'managed');

        $org = Organization::query()->create(['name' => 'Fleet', 'slug' => 'fleet']);
        $keys = app(HubKeyService::class)->generate();
        $daemon = DaemonRegistration::query()->create([
            'organization_id' => $org->id,
            'name' => 'fleet-1',
            'token_hash' => hash('sha256', 'pending'),
            'token_prefix' => 'pending',
            'hub_public_key' => $keys['public_key'],
            'hub_private_key' => $keys['private_key'],
            'status' => DaemonRegistration::STATUS_ACTIVE,
            'fingerprint' => 'fp',
        ]);

        $node = HostNode::query()->create([
            'name' => 'us-1',
            'region_code' => 'us',
            'daemon_registration_id' => $daemon->id,
            'capacity_ram_gb' => 64,
            'max_servers' => 4,
        ]);

        $server = app(ManagedProvisioningService::class)->provision([
            'plan_slug' => 'modded',
            'region_code' => 'us',
            'billing_cycle' => 'monthly',
            'server_name' => 'My Server',
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'Buyer',
        ]);

        $this->assertSame(GameServer::TYPE_MANAGED, $server->type);
        $this->assertSame($node->id, $server->host_node_id);
        $this->assertSame(8, $server->ram_gb);
    }
}
