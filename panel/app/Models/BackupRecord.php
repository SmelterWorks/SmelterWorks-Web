<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BackupRecord extends Model
{
    protected $fillable = [
        'uuid',
        'game_server_id',
        'type',
        'status',
        'bytes',
        'local_path',
        'cloud_key',
        'checksum',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BackupRecord $backup): void {
            if ($backup->uuid === null) {
                $backup->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<GameServer, $this>
     */
    public function gameServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class);
    }
}
