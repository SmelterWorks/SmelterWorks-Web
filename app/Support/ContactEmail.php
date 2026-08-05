<?php

namespace App\Support;

final class ContactEmail
{
    public static function obfuscate(string $email): string
    {
        return (string) preg_replace('/\.([a-z0-9-]+)$/i', '[dot]$1', $email);
    }
}
