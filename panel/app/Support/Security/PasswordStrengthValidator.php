<?php

namespace App\Support\Security;

use Illuminate\Validation\ValidationException;

final class PasswordStrengthValidator
{
    /**
     * @var list<string>
     */
    private const COMMON = [
        'password123',
        'qwerty123456',
        'letmein123',
        'vintagestory',
        'smelterworks',
    ];

    public function validate(string $password): void
    {
        $min = (int) config('panel.security.min_password_length', 12);

        if (strlen($password) < $min) {
            throw ValidationException::withMessages([
                'password' => "Use at least {$min} characters.",
            ]);
        }

        $classes = 0;

        if (preg_match('/[a-z]/', $password)) {
            $classes++;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $classes++;
        }

        if (preg_match('/[0-9]/', $password)) {
            $classes++;
        }

        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $classes++;
        }

        if ($classes < 3) {
            throw ValidationException::withMessages([
                'password' => 'Use upper, lower, number, and symbol in some mix.',
            ]);
        }

        $lower = strtolower($password);

        foreach (self::COMMON as $candidate) {
            if ($lower === $candidate || str_contains($lower, $candidate)) {
                throw ValidationException::withMessages([
                    'password' => 'That password is too common.',
                ]);
            }
        }
    }
}
