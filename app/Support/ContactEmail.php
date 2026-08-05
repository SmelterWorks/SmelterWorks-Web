<?php

namespace App\Support;

final class ContactEmail
{
    public static function obfuscate(string $email): string
    {
        return $email
            |> trim(...)
            |> (fn (string $value): string => str_replace('@', ' [at] ', $value))
            |> (fn (string $value): string => (string) preg_replace('/\.([a-z0-9-]+)$/i', '[dot]$1', $value));
    }
}
