<?php

namespace App\Support\Security;

final class PasswordPolicy
{
    /**
     * @return array<string, mixed>
     */
    public function requirements(): array
    {
        return [
            'minLength' => $this->minLength(),
            'requireLowercase' => $this->requireLowercase(),
            'requireUppercase' => $this->requireUppercase(),
            'requireNumber' => $this->requireNumber(),
            'requireSymbol' => $this->requireSymbol(),
            'minCharacterClasses' => $this->minCharacterClasses(),
        ];
    }

    public function minLength(): int
    {
        return max(8, (int) config('panel.security.min_password_length', 8));
    }

    public function requireLowercase(): bool
    {
        return (bool) config('panel.security.password_require_lowercase', true);
    }

    public function requireUppercase(): bool
    {
        return (bool) config('panel.security.password_require_uppercase', false);
    }

    public function requireNumber(): bool
    {
        return (bool) config('panel.security.password_require_number', true);
    }

    public function requireSymbol(): bool
    {
        return (bool) config('panel.security.password_require_symbol', false);
    }

    public function minCharacterClasses(): int
    {
        return max(0, (int) config('panel.security.password_min_character_classes', 2));
    }

    public function summary(): string
    {
        $parts = ['At least '.$this->minLength().' characters'];

        $required = [];

        if ($this->requireLowercase()) {
            $required[] = 'lowercase';
        }

        if ($this->requireUppercase()) {
            $required[] = 'uppercase';
        }

        if ($this->requireNumber()) {
            $required[] = 'a number';
        }

        if ($this->requireSymbol()) {
            $required[] = 'a symbol';
        }

        if ($required !== []) {
            $parts[] = 'including '.implode(', ', $required);
        } elseif ($this->minCharacterClasses() > 0) {
            $parts[] = 'with at least '.$this->minCharacterClasses().' character types';
        }

        return implode(', ', $parts).'.';
    }

    /**
     * @return list<string>
     */
    public function failures(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->minLength()) {
            $errors[] = 'Use at least '.$this->minLength().' characters.';
        }

        $classes = $this->characterClassCount($password);

        if ($this->requireLowercase() && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Add a lowercase letter.';
        }

        if ($this->requireUppercase() && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Add an uppercase letter.';
        }

        if ($this->requireNumber() && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Add a number.';
        }

        if ($this->requireSymbol() && ! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Add a symbol.';
        }

        if ($this->minCharacterClasses() > 0 && $classes < $this->minCharacterClasses()) {
            $errors[] = 'Use at least '.$this->minCharacterClasses().' character types (upper, lower, number, symbol).';
        }

        $lower = strtolower($password);

        foreach ($this->blockedPasswords() as $candidate) {
            if ($lower === $candidate || str_contains($lower, $candidate)) {
                $errors[] = 'That password is too common.';

                break;
            }
        }

        return $errors;
    }

    public function passes(string $password): bool
    {
        return $this->failures($password) === [];
    }

    public function strengthScore(string $password): int
    {
        if ($password === '') {
            return 0;
        }

        $score = min(40, (int) (strlen($password) / $this->minLength() * 40));
        $score += $this->characterClassCount($password) * 12;

        if ($this->passes($password)) {
            $score += 12;
        }

        return min(100, $score);
    }

    private function characterClassCount(string $password): int
    {
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

        return $classes;
    }

    /**
     * @return list<string>
     */
    private function blockedPasswords(): array
    {
        $configured = config('panel.security.password_blocklist');

        if (! is_array($configured)) {
            return [
                'password123',
                'qwerty123456',
                'letmein123',
                'vintagestory',
                'smelterworks',
            ];
        }

        return array_values(array_filter($configured, is_string(...)));
    }
}
