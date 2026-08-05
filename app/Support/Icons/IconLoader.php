<?php

namespace App\Support\Icons;

final class IconLoader
{
    private const PACKS = ['lucide', 'simple', 'brands', 'flags'];

    public function path(string $pack, string $name): ?string
    {
        if (! in_array($pack, self::PACKS, true)) {
            return null;
        }

        if (preg_match('/^[a-z0-9-]+$/i', $name) !== 1) {
            return null;
        }

        $packRoot = realpath(public_path('icons/'.$pack));
        $candidate = public_path('icons/'.$pack.'/'.$name.'.svg');
        $resolved = realpath($candidate);

        if ($packRoot === false || $resolved === false) {
            return null;
        }

        if (! str_starts_with($resolved, $packRoot.DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (! is_file($resolved)) {
            return null;
        }

        return $resolved;
    }

    public function contents(string $pack, string $name): string
    {
        $path = $this->path($pack, $name);

        if ($path === null) {
            return '';
        }

        $svg = file_get_contents($path);

        return is_string($svg) ? $svg : '';
    }
}
