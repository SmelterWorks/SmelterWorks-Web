<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HostNode extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DRAINING = 'draining';

    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'uuid',
        'name',
        'region_code',
        'daemon_registration_id',
        'capacity_ram_gb',
        'used_ram_gb',
        'max_servers',
        'active_servers',
        'status',
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
        static::creating(function (HostNode $node): void {
            if ($node->uuid === null) {
                $node->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<DaemonRegistration, $this>
     */
    public function daemon(): BelongsTo
    {
        return $this->belongsTo(DaemonRegistration::class, 'daemon_registration_id');
    }

    /**
     * @return HasMany<GameServer, $this>
     */
    public function gameServers(): HasMany
    {
        return $this->hasMany(GameServer::class);
    }

    public function hasCapacity(int $ramGb): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->active_servers < $this->max_servers
            && ($this->used_ram_gb + $ramGb) <= $this->capacity_ram_gb;
    }
}
