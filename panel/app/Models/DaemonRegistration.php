<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class DaemonRegistration extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'uuid',
        'organization_id',
        'name',
        'token_hash',
        'token_prefix',
        'hub_public_key',
        'hub_private_key',
        'fingerprint',
        'status',
        'token_expires_at',
        'last_seen_at',
        'last_ip',
        'metadata',
    ];

    protected $hidden = [
        'token_hash',
        'hub_private_key',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DaemonRegistration $daemon): void {
            if ($daemon->uuid === null) {
                $daemon->uuid = (string) Str::uuid();
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
     * @return HasOne<GameServer, $this>
     */
    public function gameServer(): HasOne
    {
        return $this->hasOne(GameServer::class);
    }
}
