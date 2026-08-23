<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GameServer extends Model
{
    public const TYPE_MANAGED = 'managed';

    public const TYPE_BYOS = 'byos';

    protected $fillable = [
        'uuid',
        'organization_id',
        'daemon_registration_id',
        'host_node_id',
        'name',
        'type',
        'plan_slug',
        'stripe_subscription_id',
        'billing_cycle',
        'status',
        'region_code',
        'ram_gb',
        'storage_gb',
        'connect_address',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GameServer $server): void {
            if ($server->uuid === null) {
                $server->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<DaemonRegistration, $this>
     */
    public function daemon(): BelongsTo
    {
        return $this->belongsTo(DaemonRegistration::class, 'daemon_registration_id');
    }

    /**
     * @return BelongsTo<HostNode, $this>
     */
    public function hostNode(): BelongsTo
    {
        return $this->belongsTo(HostNode::class);
    }

    /**
     * @return HasMany<BackupRecord, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(BackupRecord::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
