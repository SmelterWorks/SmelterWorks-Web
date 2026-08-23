<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HostingPurchase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'plan_slug',
        'region_code',
        'billing_cycle',
        'amount_usd',
        'customer_name',
        'customer_email',
        'server_name',
        'status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'provisioned_server_uuid',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $purchase): void {
            if (blank($purchase->uuid)) {
                $purchase->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function holdsStock(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID], true);
    }
}
