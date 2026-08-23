<?php

namespace App\Support\Hosting;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HostingCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function plans(): array
    {
        /** @var list<array<string, mixed>> $plans */
        $plans = config('smelterworks.hosting.plans', []);

        return $plans;
    }

    /**
     * @return array<string, mixed>
     */
    public function plan(string $slug): array
    {
        $plan = collect($this->plans())->firstWhere('slug', $slug);

        if ($plan === null) {
            throw new NotFoundHttpException("Hosting plan [{$slug}] was not found.");
        }

        return $plan;
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function regions(): array
    {
        /** @var list<array{code: string, label: string}> $regions */
        $regions = config('smelterworks.hosting.regions', []);

        return $regions;
    }

    public function regionLabel(string $code): string
    {
        $region = collect($this->regions())->firstWhere('code', $code);

        if ($region === null) {
            $byosPlan = collect($this->plans())->first(
                fn (array $plan): bool => ($plan['type'] ?? null) === 'byos'
                    && ($plan['region_code'] ?? null) === $code,
            );

            if ($byosPlan !== null) {
                return (string) ($byosPlan['region_label'] ?? 'Your hardware');
            }

            throw ValidationException::withMessages([
                'region_code' => 'Choose a valid region.',
            ]);
        }

        return $region['label'];
    }

    public function isByosPlan(string $slug): bool
    {
        $plan = collect($this->plans())->firstWhere('slug', $slug);

        return is_array($plan) && ($plan['type'] ?? null) === 'byos';
    }

    public function isByos(array $plan): bool
    {
        return ($plan['type'] ?? null) === 'byos';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cloudBackupTiers(): array
    {
        /** @var list<array<string, mixed>> $tiers */
        $tiers = config('smelterworks.hosting.cloud_backup_tiers', []);

        return $tiers;
    }
}
