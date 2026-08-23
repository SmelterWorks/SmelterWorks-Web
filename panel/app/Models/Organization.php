<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = ['name', 'slug'];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<GameServer, $this>
     */
    public function gameServers(): HasMany
    {
        return $this->hasMany(GameServer::class);
    }

    /**
     * @return HasMany<DaemonRegistration, $this>
     */
    public function daemons(): HasMany
    {
        return $this->hasMany(DaemonRegistration::class);
    }

    /**
     * @return HasMany<Subuser, $this>
     */
    public function subusers(): HasMany
    {
        return $this->hasMany(Subuser::class);
    }
}
