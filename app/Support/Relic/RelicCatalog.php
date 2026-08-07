<?php

namespace App\Support\Relic;

use App\Support\Updates\Data\ChannelManifest;
use App\Support\Updates\Data\MirroredAsset;
use App\Support\Updates\UpdateMirrorService;

class RelicCatalog
{
    private const PRODUCT = 'relic';

    public function __construct(
        private readonly UpdateMirrorService $mirror,
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
        $relic = $this->withStableTag($relic);

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

        $stableManifest = $this->mirror->getChannelManifest(self::PRODUCT, 'stable');
        $stable = $this->channelSummary($stableManifest, (string) $relic['releases_url']);
        $downloads = $this->buildDownloads($relic, $stableManifest, 'stable');

        $nightlyEnabled = (bool) data_get($relic, 'nightly.enabled', true);
        $nightlyManifest = $nightlyEnabled
            ? $this->mirror->getChannelManifest(self::PRODUCT, 'nightly')
            : null;

        $nightly = [
            'enabled' => $nightlyEnabled,
            'available' => $nightlyManifest !== null,
            'tag' => $this->displayTag($nightlyManifest),
            'html_url' => $nightlyManifest?->releaseNotesUrl ?: (string) $relic['nightly_list_url'],
            'published_at' => $nightlyManifest?->publishedAt,
            'downloads' => $nightlyEnabled
                ? $this->buildDownloads($relic, $nightlyManifest, 'nightly')
                : [],
        ];

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
     * @return array<string, mixed>
     */
    private function withStableTag(array $relic): array
    {
        $manifest = $this->mirror->getChannelManifest(self::PRODUCT, 'stable');
        $relic['stable_tag'] = $this->displayTag($manifest);

        return $relic;
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
        $stableManifest = $this->mirror->getChannelManifest(self::PRODUCT, 'stable');
        $releasesRepo = rtrim((string) ($relic['releases_repo_url'] ?? ''), '/');

        if (filled($relic['releases_url'] ?? null)) {
            $releasesUrl = (string) $relic['releases_url'];
        } elseif (filled($stableManifest?->releaseNotesUrl)) {
            $releasesUrl = $stableManifest->releaseNotesUrl;
        } elseif ($stableManifest !== null && $releasesRepo !== '') {
            $releasesUrl = $releasesRepo.'/releases/latest';
        } else {
            $releasesUrl = '';
        }

        $relic['releases_url'] = $releasesUrl;
        $relic['nightly_list_url'] = $releasesRepo !== '' ? $releasesRepo.'/releases' : '';

        return $relic;
    }

    /**
     * @param  array<string, mixed>  $relic
     * @return list<array<string, mixed>>
     */
    private function buildDownloads(array $relic, ?ChannelManifest $manifest, string $channel): array
    {
        /** @var list<array<string, mixed>> $downloads */
        $downloads = $relic['downloads'] ?? [];

        return collect($downloads)
            ->map(function (array $download) use ($manifest, $channel): array {
                $rid = (string) ($download['rid'] ?? '');
                $download['channel'] = $channel;
                $download['rid'] = $rid;

                /** @var list<array<string, mixed>> $formats */
                $formats = $download['formats'] ?? [];

                if ($formats !== []) {
                    $resolvedFormats = collect($formats)
                        ->map(function (array $format) use ($manifest, $rid): array {
                            $installKind = (string) ($format['install_kind'] ?? '');
                            $asset = $rid !== '' && $installKind !== '' && $manifest !== null
                                ? $this->assetForInstallKind($manifest, $rid, $installKind)
                                : null;

                            return [
                                'id' => (string) ($format['id'] ?? ''),
                                'label' => (string) ($format['label'] ?? ''),
                                'install_kind' => $installKind,
                                'available' => $asset !== null,
                                'url' => $asset !== null && $manifest !== null
                                    ? $this->mirror->fileUrl(self::PRODUCT, $manifest->version, $asset->filename)
                                    : '',
                            ];
                        })
                        ->values()
                        ->all();

                    $defaultFormat = $this->resolveDefaultFormat(
                        $resolvedFormats,
                        (string) ($download['default_format'] ?? ''),
                    );

                    $download['formats'] = $resolvedFormats;
                    $download['default_format'] = $defaultFormat['id'] ?? '';
                    $download['available'] = $defaultFormat !== null;
                    $download['url'] = $defaultFormat['url'] ?? '';
                } else {
                    $asset = $rid !== '' && $manifest !== null
                        ? $this->assetForRid($manifest, $rid)
                        : null;

                    $download['available'] = $asset !== null;
                    $download['url'] = $asset !== null && $manifest !== null
                        ? $this->mirror->fileUrl(self::PRODUCT, $manifest->version, $asset->filename)
                        : '';
                }

                if ($channel === 'nightly') {
                    $download['detail'] = $this->nightlyDetail($download, $download['available']);
                }

                return $download;
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $formats
     * @return array<string, mixed>|null
     */
    private function resolveDefaultFormat(array $formats, string $preferredId): ?array
    {
        if ($preferredId !== '') {
            $preferred = collect($formats)->first(
                fn (array $format): bool => ($format['id'] ?? '') === $preferredId && ($format['available'] ?? false),
            );

            if ($preferred !== null) {
                return $preferred;
            }
        }

        return collect($formats)->firstWhere('available', true);
    }

    private function assetForInstallKind(ChannelManifest $manifest, string $rid, string $installKind): ?MirroredAsset
    {
        foreach ($manifest->assets as $asset) {
            if ($asset->rid === $rid && $asset->installKind === $installKind) {
                return $asset;
            }
        }

        return null;
    }

    private function assetForRid(ChannelManifest $manifest, string $rid): ?MirroredAsset
    {
        foreach ($manifest->assets as $asset) {
            if ($asset->rid === $rid) {
                return $asset;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     available: bool,
     *     tag: string|null,
     *     html_url: string|null,
     *     published_at: string|null
     * }
     */
    private function channelSummary(?ChannelManifest $manifest, string $fallbackUrl): array
    {
        if ($manifest === null) {
            return [
                'available' => false,
                'tag' => null,
                'html_url' => null,
                'published_at' => null,
            ];
        }

        return [
            'available' => true,
            'tag' => $this->displayTag($manifest),
            'html_url' => $manifest->releaseNotesUrl !== '' ? $manifest->releaseNotesUrl : $fallbackUrl,
            'published_at' => $manifest->publishedAt,
        ];
    }

    private function displayTag(?ChannelManifest $manifest): ?string
    {
        if ($manifest === null) {
            return null;
        }

        if (preg_match('/^\d+\./', $manifest->version)) {
            return 'v'.$manifest->version;
        }

        return $manifest->version;
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
