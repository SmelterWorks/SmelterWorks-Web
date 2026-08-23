<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MigrationJob extends Model
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'source_server_id',
        'destination_server_id',
        'status',
        'bytes',
        'package_hash',
        'staging_key',
        'staging_expires_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'staging_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MigrationJob $job): void {
            if ($job->uuid === null) {
                $job->uuid = (string) Str::uuid();
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
     * @return BelongsTo<GameServer, $this>
     */
    public function sourceServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class, 'source_server_id');
    }

    /**
     * @return BelongsTo<GameServer, $this>
     */
    public function destinationServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class, 'destination_server_id');
    }
}
