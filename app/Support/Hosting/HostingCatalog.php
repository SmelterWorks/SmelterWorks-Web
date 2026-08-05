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
            throw ValidationException::withMessages([
                'region_code' => 'Choose a valid region.',
            ]);
        }

        return $region['label'];
    }
}
