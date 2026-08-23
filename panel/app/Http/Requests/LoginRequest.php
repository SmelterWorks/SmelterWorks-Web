<?php

namespace App\Http\Requests;

use App\Rules\ValidAltcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
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
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'altcha' => [
                Rule::requiredIf(fn (): bool => (bool) config('panel.altcha.enabled')),
                'string',
                new ValidAltcha,
            ],
        ];
    }
}
