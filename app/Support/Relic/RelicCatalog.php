<?php

namespace App\Support\Relic;

class RelicCatalog
{
    public function __construct(
        private readonly RelicGitHubReleases $releases,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forView(?array $relic = null): array
    {
        /** @var array<string, mixed> $relic */
        $relic ??= config('smelterworks.relic');
        $relic['platforms'] = $this->normalizePlatforms($relic['platforms'] ?? []);
        $relic = $this->withReleaseUrls($relic);
        $relic = $this->withPreviewAssets($relic);

        return $relic;
    }

    /**
     * @return array{
     *     relic: array<string, mixed>,
     *     downloads: list<array<string, mixed>>,
     *     stable: array{
     *         available: bool,
     *         tag: string|null,
     *         html_url: string|null,
     *         published_at: string|null
     *     },
     *     nightly: array{
     *         enabled: bool,
     *         available: bool,
     *         tag: string|null,
     *         html_url: string|null,
     *         published_at: string|null,
     *         downloads: list<array<string, mixed>>
     *     }
     * }
     */
    public function forDownloadPage(?array $relic = null): array
    {
        $relic = $this->forView($relic);
        $parsed = $this->parsedReleasesRepo($relic);

        $stableRelease = $parsed === null
            ? null
            : $this->releases->latestStable($parsed['owner'], $parsed['repo']);

        $stable = [
            'available' => $stableRelease !== null,
            'tag' => $stableRelease['tag'] ?? null,
            'html_url' => $stableRelease !== null
                ? ($stableRelease['html_url'] !== '' ? $stableRelease['html_url'] : (string) $relic['releases_url'])
                : null,
            'published_at' => $stableRelease['published_at'] ?? null,
        ];

        $downloads = $this->stableDownloads($relic, $stableRelease);

        $nightlyEnabled = (bool) data_get($relic, 'nightly.enabled', true);
        $nightly = [
            'enabled' => $nightlyEnabled,
            'available' => false,
            'tag' => null,
            'html_url' => null,
            'published_at' => null,
            'downloads' => [],
        ];

        if ($nightlyEnabled && $parsed !== null) {
            $latest = $this->releases->latestNightly($parsed['owner'], $parsed['repo']);

            if ($latest !== null) {
                $nightly['available'] = true;
                $nightly['tag'] = $latest['tag'];
                $nightly['html_url'] = $latest['html_url'] !== ''
                    ? $latest['html_url']
                    : (string) $relic['nightly_list_url'];
                $nightly['published_at'] = $latest['published_at'];
                $nightly['downloads'] = $this->nightlyDownloads($relic, $latest);
            } else {
                $nightly['downloads'] = $this->nightlyFallbackDownloads($relic);
            }
        }

        return [
            'relic' => $relic,
            'downloads' => $downloads,
            'stable' => $stable,
            'nightly' => $nightly,
        ];
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
     * @param  array<string, mixed>  $relic
     * @return array{owner: string, repo: string}|null
     */
    private function parsedReleasesRepo(array $relic): ?array
    {
        return $this->releases->parseRepo((string) ($relic['releases_repo_url'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $relic
     * @return array<string, mixed>
     */
    private function withPreviewAssets(array $relic): array
    {
        if (filled($relic['preview_url'] ?? null)) {
            $url = (string) $relic['preview_url'];

            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $relic['preview_url'] = asset($url);
            }

            return $relic;
        }

        /** @var array{webp: string, jpg: string} $assets */
        $assets = $relic['preview_assets'] ?? [
            'webp' => 'images/relic/home-relic-default.webp',
            'jpg' => 'images/relic/home-relic-default.jpg',
        ];

        $relic['preview_webp'] = asset($assets['webp']);
        $relic['preview_fallback'] = asset($assets['jpg']);
        $relic['preview_url'] = $relic['preview_webp'];

        return $relic;
    }

    /**
     * @param  array<string, mixed>  $relic
     * @return array<string, mixed>
     */
    private function withReleaseUrls(array $relic): array
    {
        $releasesRepo = rtrim((string) ($relic['releases_repo_url'] ?? ''), '/');
        $releasesUrl = filled($relic['releases_url'] ?? null)
            ? (string) $relic['releases_url']
            : ($releasesRepo !== '' ? $releasesRepo.'/releases/latest' : '');

        $relic['releases_url'] = $releasesUrl;
        $relic['nightly_list_url'] = $releasesRepo !== '' ? $releasesRepo.'/releases' : '';

        return $relic;
    }

    /**
     * @param  array<string, mixed>  $relic
     * @param  array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }|null  $stable
     * @return list<array<string, mixed>>
     */
    private function stableDownloads(array $relic, ?array $stable): array
    {
        /** @var list<array<string, mixed>> $downloads */
        $downloads = $relic['downloads'] ?? [];

        if ($stable === null) {
            return collect($downloads)
                ->map(function (array $download): array {
                    $download['channel'] = 'stable';
                    $download['available'] = false;
                    $download['url'] = '';
                    $download['rid'] = (string) ($download['rid'] ?? '');

                    return $download;
                })
                ->values()
                ->all();
        }

        $fallback = $stable['html_url'] !== ''
            ? $stable['html_url']
            : (string) $relic['releases_url'];

        return collect($downloads)
            ->map(function (array $download) use ($stable, $fallback): array {
                $rid = (string) ($download['rid'] ?? '');
                $assetUrl = $rid !== ''
                    ? $this->releases->assetUrlForRid($stable['assets'], $rid)
                    : null;

                $download['channel'] = 'stable';
                $download['available'] = $assetUrl !== null || $fallback !== '';
                $download['url'] = $assetUrl ?? $fallback;
                $download['rid'] = $rid;

                return $download;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $relic
     * @param  array{
     *     tag: string,
     *     html_url: string,
     *     published_at: string|null,
     *     assets: list<array{name: string, browser_download_url: string}>
     * }  $nightly
     * @return list<array<string, mixed>>
     */
    private function nightlyDownloads(array $relic, array $nightly): array
    {
        $fallback = $nightly['html_url'] !== ''
            ? $nightly['html_url']
            : (string) $relic['nightly_list_url'];

        /** @var list<array<string, mixed>> $downloads */
        $downloads = $relic['downloads'] ?? [];

        return collect($downloads)
            ->map(function (array $download) use ($nightly): array {
                $rid = (string) ($download['rid'] ?? '');
                $assetUrl = $rid !== ''
                    ? $this->releases->assetUrlForRid($nightly['assets'], $rid)
                    : null;

                $download['channel'] = 'nightly';
                $download['available'] = $assetUrl !== null;
                $download['url'] = $assetUrl ?? '';
                $download['rid'] = $rid;
                $download['detail'] = $this->nightlyDetail($download, $assetUrl !== null);

                return $download;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $relic
     * @return list<array<string, mixed>>
     */
    private function nightlyFallbackDownloads(array $relic): array
    {
        /** @var list<array<string, mixed>> $downloads */
        $downloads = $relic['downloads'] ?? [];

        return collect($downloads)
            ->map(function (array $download): array {
                $download['channel'] = 'nightly';
                $download['available'] = false;
                $download['url'] = '';
                $download['rid'] = (string) ($download['rid'] ?? '');
                $download['detail'] = $this->nightlyDetail($download, false);

                return $download;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $download
     */
    private function nightlyDetail(array $download, bool $hasAsset): string
    {
        $base = (string) ($download['detail'] ?? $download['label'] ?? 'Build');

        if ($hasAsset) {
            return $base.' · nightly pre-release';
        }

        return $base.' · no nightly build yet';
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
