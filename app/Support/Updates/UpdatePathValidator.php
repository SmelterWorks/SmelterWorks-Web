<?php

namespace App\Support\Updates;

final class UpdatePathValidator
{
    public function isValidProduct(string $product): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]*$/', $product);
    }

    public function isValidChannel(string $channel): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]*$/', $channel);
    }

    public function isValidVersion(string $version): bool
    {
        return $version !== ''
            && ! str_contains($version, '/')
            && ! str_contains($version, '\\')
            && ! str_contains($version, '..')
            && (bool) preg_match('/^[a-zA-Z0-9._-]+$/', $version);
    }

    public function isValidFilename(string $filename): bool
    {
        return $filename !== ''
            && $filename !== '.meta.json'
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '\\')
            && ! str_contains($filename, '..')
            && (bool) preg_match('/^[a-zA-Z0-9._-]+$/', $filename);
    }
}
