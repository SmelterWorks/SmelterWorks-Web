<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_subnet',
        'user_agent_family',
        'last_activity_at',
        'step_up_verified_at',
        'revoked',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'step_up_verified_at' => 'datetime',
            'revoked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
