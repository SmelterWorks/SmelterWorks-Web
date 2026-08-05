<?php

namespace App\Support;

final class ContactEmail
{
    public static function obfuscate(string $email): string
    {
        $obfuscated = str_replace('@', ' [at] ', $email);

        return (string) preg_replace('/\.([a-z0-9-]+)$/i', '[dot]$1', $obfuscated);
    }
}
