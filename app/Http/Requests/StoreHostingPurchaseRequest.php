<?php

namespace App\Http\Requests;

use App\Support\Hosting\HostingCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostingPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $catalog = app(HostingCatalog::class);
        $planSlugs = collect(config('smelterworks.hosting.plans'))->pluck('slug')->all();
        $regionCodes = collect(config('smelterworks.hosting.regions'))->pluck('code')->all();
        $byosRegion = collect(config('smelterworks.hosting.plans'))
            ->first(fn (array $plan): bool => ($plan['type'] ?? null) === 'byos')['region_code'] ?? 'your-hardware';
        $allowedRegions = array_merge($regionCodes, [$byosRegion]);

        return [
            'plan_slug' => ['required', 'string', Rule::in($planSlugs)],
            'region_code' => [
                'required',
                'string',
                Rule::in($allowedRegions),
                function (string $attribute, mixed $value, \Closure $fail) use ($catalog, $byosRegion): void {
                    $planSlug = (string) $this->input('plan_slug');

                    if ($catalog->isByosPlan($planSlug) && $value !== $byosRegion) {
                        $fail('BYOS plans use your own hardware region only.');
                    }

                    if (! $catalog->isByosPlan($planSlug) && $value === $byosRegion) {
                        $fail('Choose a US or Germany region for managed hosting.');
                    }
                },
            ],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'server_name' => ['nullable', 'string', 'max:64'],
        ];
    }
}
