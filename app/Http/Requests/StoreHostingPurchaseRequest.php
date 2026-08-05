<?php

namespace App\Http\Requests;

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
        $planSlugs = collect(config('smelterworks.hosting.plans'))->pluck('slug')->all();
        $regionCodes = collect(config('smelterworks.hosting.regions'))->pluck('code')->all();

        return [
            'plan_slug' => ['required', 'string', Rule::in($planSlugs)],
            'region_code' => ['required', 'string', Rule::in($regionCodes)],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'server_name' => ['nullable', 'string', 'max:64'],
        ];
    }
}
