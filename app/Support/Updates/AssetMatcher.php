<?php

namespace App\Support\Updates;

use App\Support\Updates\Data\UpstreamAsset;
use App\Support\Updates\Data\UpstreamRelease;

final class AssetMatcher
{
    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<array{
     *     installKind: string,
     *     rid: string,
     *     asset: UpstreamAsset
     * }>
     */
    public function match(UpstreamRelease $release, array $rules): array
    {
        $matched = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rid = (string) ($rule['rid'] ?? '');
            $installKind = (string) ($rule['installKind'] ?? '');

            if ($rid === '' || $installKind === '') {
                continue;
            }

            $asset = $this->matchRule($release->assets, $rule);

            if ($asset === null) {
                continue;
            }

            $matched[] = [
                'installKind' => $installKind,
                'rid' => $rid,
                'asset' => $asset,
            ];
        }

        return $matched;
    }

    /**
     * @param  list<UpstreamAsset>  $assets
     * @param  array<string, mixed>  $rule
     */
    private function matchRule(array $assets, array $rule): ?UpstreamAsset
    {
        /** @var list<string> $matchPatterns */
        $matchPatterns = $rule['match'] ?? [];
        /** @var list<string> $preferPatterns */
        $preferPatterns = $rule['prefer'] ?? [];
        /** @var list<string> $rejectPatterns */
        $rejectPatterns = $rule['reject'] ?? [];

        $candidates = collect($assets)
            ->filter(fn (UpstreamAsset $asset): bool => $this->matchesAny($asset->name, $matchPatterns))
            ->reject(fn (UpstreamAsset $asset): bool => $this->matchesAny($asset->name, $rejectPatterns))
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($preferPatterns !== []) {
            foreach ($preferPatterns as $pattern) {
                $preferred = $candidates->first(
                    fn (UpstreamAsset $asset): bool => $this->matchesPattern($asset->name, $pattern),
                );

                if ($preferred !== null) {
                    return $preferred;
                }
            }
        }

        return $candidates->first();
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesAny(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matchesPattern($name, (string) $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $name, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/i';

        return (bool) preg_match($regex, $name);
    }
}
