<?php

namespace App\Support\Migration;

use App\Models\MigrationJob;
use Illuminate\Validation\ValidationException;

final class MigrationRateLimiter
{
    public function ensureAllowed(int $organizationId): void
    {
        $daily = (int) config('panel.migration.per_account_daily', 3);
        $concurrent = (int) config('panel.migration.concurrent_per_account', 1);

        $startedToday = MigrationJob::query()
            ->where('organization_id', $organizationId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($startedToday >= $daily) {
            throw ValidationException::withMessages([
                'migration' => 'Daily migration limit reached for this account.',
            ]);
        }

        $active = MigrationJob::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['pending', 'packaging', 'transferring', 'importing'])
            ->count();

        if ($active >= $concurrent) {
            throw ValidationException::withMessages([
                'migration' => 'A migration is already running for this account.',
            ]);
        }
    }
}
