<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentCommand extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'daemon_registration_id',
        'game_server_id',
        'type',
        'status',
        'payload',
        'result',
        'error',
        'dispatched_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AgentCommand $command): void {
            if ($command->uuid === null) {
                $command->uuid = (string) Str::uuid();
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
     * @return BelongsTo<GameServer, $this>
     */
    public function gameServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class);
    }
}
