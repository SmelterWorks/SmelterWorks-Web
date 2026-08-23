<?php

namespace App\Rules;

use App\Support\Altcha\AltchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidAltcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $altcha = app(AltchaService::class);

        if (! $altcha->enabled()) {
            return;
        }

        if (! is_string($value) || ! $altcha->verify($value)) {
            $fail('Captcha verification failed. Try again.');
        }
    }
}
