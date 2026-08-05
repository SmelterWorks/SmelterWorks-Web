<?php

namespace App\Support\Relic;

class RelicCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function forView(?array $relic = null): array
    {
        /** @var array<string, mixed> $relic */
        $relic ??= config('smelterworks.relic');
        $relic['platforms'] = $this->normalizePlatforms($relic['platforms'] ?? []);

        return $relic;
    }

    /**
     * @param  list<mixed>  $platforms
     * @return list<array{icon: string, label: string, detail: string|null}>
     */
    public function normalizePlatforms(array $platforms): array
    {
        return collect($platforms)
            ->map(function (mixed $platform): array {
                if (is_array($platform)) {
                    return [
                        'icon' => (string) ($platform['icon'] ?? 'windows'),
                        'label' => (string) ($platform['label'] ?? ''),
                        'detail' => filled($platform['detail'] ?? null) ? (string) $platform['detail'] : null,
                    ];
                }

                return $this->platformFromLabel((string) $platform);
            })
            ->filter(fn (array $platform): bool => $platform['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{icon: string, label: string, detail: string|null}
     */
    private function platformFromLabel(string $label): array
    {
        $lower = strtolower($label);

        if (str_contains($lower, 'windows')) {
            return ['icon' => 'windows', 'label' => 'Windows', 'detail' => $label];
        }

        if (str_contains($lower, 'linux')) {
            return ['icon' => 'linux', 'label' => 'Linux', 'detail' => $label];
        }

        if (str_contains($lower, 'mac')) {
            return ['icon' => 'macos', 'label' => 'macOS', 'detail' => $label];
        }

        return ['icon' => 'windows', 'label' => $label, 'detail' => null];
    }
}
