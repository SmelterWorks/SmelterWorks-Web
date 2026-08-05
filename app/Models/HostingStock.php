<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostingStock extends Model
{
    protected $fillable = [
        'region_code',
        'plan_slug',
        'capacity',
        'sold',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sold' => 'integer',
        ];
    }

    public function remaining(): int
    {
        return max(0, $this->capacity - $this->sold);
    }

    public function isAvailable(): bool
    {
        return $this->remaining() > 0;
    }
}
