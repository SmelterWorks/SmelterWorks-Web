<?php

namespace App\Support\Hosting;

use App\Models\GameServer;
use App\Models\HostNode;
use App\Models\Organization;
use App\Support\Agent\AgentCommandService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManagedProvisioningService
{
    public function __construct(
        private readonly AgentCommandService $commands,
    ) {}

    /**
     * @param  array{plan_slug: string, region_code: string, billing_cycle: string, server_name: string, customer_email: string, customer_name: string, purchase_uuid?: string}  $input
     */
    public function provision(array $input): GameServer
    {
        if (config('panel.mode') !== 'managed') {
            throw ValidationException::withMessages([
                'mode' => 'Managed provisioning is disabled in selfhost mode.',
            ]);
        }

        $plan = $this->plan($input['plan_slug']);

        return DB::transaction(function () use ($input, $plan): GameServer {
            $organization = $this->resolveOrganization($input);

            $node = HostNode::query()
                ->where('region_code', $input['region_code'])
                ->where('status', HostNode::STATUS_ACTIVE)
                ->orderBy('active_servers')
                ->lockForUpdate()
                ->get()
                ->first(fn (HostNode $candidate): bool => $candidate->hasCapacity((int) $plan['ram_gb']));

            if ($node === null) {
                throw ValidationException::withMessages([
                    'region_code' => 'No managed capacity available in this region.',
                ]);
            }

            $server = GameServer::query()->create([
                'organization_id' => $organization->id,
                'host_node_id' => $node->id,
                'daemon_registration_id' => $node->daemon_registration_id,
                'name' => $input['server_name'],
                'type' => GameServer::TYPE_MANAGED,
                'plan_slug' => $input['plan_slug'],
                'billing_cycle' => $input['billing_cycle'],
                'status' => 'provisioning',
                'region_code' => $input['region_code'],
                'ram_gb' => $plan['ram_gb'],
                'storage_gb' => $plan['storage_gb'],
                'metadata' => [
                    'purchase_uuid' => $input['purchase_uuid'] ?? null,
                    'customer_name' => $input['customer_name'],
                ],
            ]);

            $node->increment('active_servers');
            $node->increment('used_ram_gb', (int) $plan['ram_gb']);

            if ($node->daemon !== null) {
                $this->commands->dispatch($node->daemon, 'power.start', [
                    'server_uuid' => $server->uuid,
                ], $server);
            }

            return $server;
        });
    }

    /**
     * @return array{slug: string, ram_gb: int, storage_gb: int, price_monthly: int, price_yearly: int}
     */
    public function plan(string $slug): array
    {
        $plan = config('panel.plans.'.$slug);

        if (! is_array($plan)) {
            throw ValidationException::withMessages([
                'plan_slug' => 'Unknown hosting plan.',
            ]);
        }

        return $plan;
    }

    /**
     * @param  array{customer_email: string, customer_name: string}  $input
     */
    private function resolveOrganization(array $input): Organization
    {
        $organization = Organization::query()
            ->whereHas('users', fn ($query) => $query->where('email', $input['customer_email']))
            ->first();

        if ($organization !== null) {
            return $organization;
        }

        $organization = Organization::query()->create([
            'name' => $input['customer_name'],
            'slug' => Str::slug($input['customer_name']).'-'.Str::lower(Str::random(4)),
            'billing_email' => $input['customer_email'],
        ]);

        return $organization;
    }
}
