<?php

namespace App\Support\Security;

use Illuminate\Validation\ValidationException;

final class PasswordStrengthValidator
{
    public function __construct(
        private readonly PasswordPolicy $policy,
    ) {}

    public function validate(string $password): void
    {
        $failures = $this->policy->failures($password);

        if ($failures !== []) {
            throw ValidationException::withMessages([
                'password' => $failures[0],
            ]);
        }
    }
}
