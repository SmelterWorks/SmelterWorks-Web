<?php

namespace App\Support\Updates;

use App\Support\Updates\Data\ChannelManifest;
use App\Support\Updates\Data\MirroredAsset;
use App\Support\Updates\Data\UpstreamAsset;
use App\Support\Updates\Data\UpstreamRelease;
use App\Support\Updates\Sources\UpdateSourceResolver;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class UpdateMirrorService
{
    private const LOCK_PREFIX = 'updates.warm.';

    public function __construct(
        private readonly UpdateProductRegistry $registry,
        private readonly UpdateSourceResolver $sources,
        private readonly AssetMatcher $matcher,
        private readonly UpstreamUrlValidator $urlValidator,
        private readonly UpdatePathValidator $paths,
    ) {}

    public function warmAll(): void
    {
        foreach ($this->registry->enabledProducts() as $product) {
            $this->warmProduct($product);
        }
    }

    public function warmProduct(string $productSlug, ?string $channelSlug = null): void
    {
        $product = $this->registry->product($productSlug);

        if ($product === null) {
            return;
        }

        /** @var array<string, mixed> $channels */
        $channels = $product['channels'] ?? [];

        if ($channelSlug !== null) {
            if ($this->registry->channel($productSlug, $channelSlug) !== null) {
                $this->warmChannel($productSlug, $channelSlug);
            }

            $this->purgeOrphanVersions($productSlug);

            return;
        }

        foreach (array_keys($channels) as $slug) {
            if ($this->registry->channel($productSlug, (string) $slug) !== null) {
                $this->warmChannel($productSlug, (string) $slug);
            }
        }

        $this->purgeOrphanVersions($productSlug);
    }

    public function warmChannel(string $productSlug, string $channelSlug): void
    {
        $lock = Cache::lock(self::LOCK_PREFIX."{$productSlug}.{$channelSlug}", $this->registry->warmLockSeconds());

        if (! $lock->get()) {
            return;
        }

        try {
            $this->mirrorChannel($productSlug, $channelSlug);
        } catch (\Throwable $exception) {
            Log::warning('Update channel warm failed.', [
                'product' => $productSlug,
                'channel' => $channelSlug,
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }

    public function getChannelManifest(string $productSlug, string $channelSlug): ?ChannelManifest
    {
        if ($this->registry->channel($productSlug, $channelSlug) === null) {
            return null;
        }

        $disk = $this->disk();
        $path = $this->channelManifestPath($productSlug, $channelSlug);

        if (! $disk->exists($path)) {
            return null;
        }

        $decoded = json_decode($disk->get($path), true);

        if (! is_array($decoded)) {
            return null;
        }

        return ChannelManifest::fromStorageArray($decoded);
    }

    public function assetExists(string $productSlug, string $version, string $filename): bool
    {
        if (
            ! $this->paths->isValidProduct($productSlug)
            || ! $this->paths->isValidVersion($version)
            || ! $this->paths->isValidFilename($filename)
        ) {
            return false;
        }

        $manifest = $this->findManifestForVersion($productSlug, $version);

        if ($manifest === null) {
            return false;
        }

        foreach ($manifest->assets as $asset) {
            if ($asset->filename === $filename) {
                return $this->disk()->exists($this->assetRelativePath($productSlug, $version, $filename));
            }
        }

        return false;
    }

    public function assetRelativePath(string $productSlug, string $version, string $filename): string
    {
        return "updates/{$productSlug}/{$version}/{$filename}";
    }

    public function fileUrl(string $productSlug, string $version, string $filename): string
    {
        $base = $this->registry->publicBaseUrl();

        return "{$base}/files/{$productSlug}/{$version}/{$filename}";
    }

    /**
     * @return list<string>
     */
    public function referencedVersions(string $productSlug): array
    {
        $product = $this->registry->product($productSlug);

        if ($product === null) {
            return [];
        }

        $versions = [];

        foreach (array_keys($product['channels'] ?? []) as $channelSlug) {
            $manifest = $this->getChannelManifest($productSlug, (string) $channelSlug);

            if ($manifest !== null) {
                $versions[] = $manifest->version;
            }
        }

        return array_values(array_unique($versions));
    }

    private function mirrorChannel(string $productSlug, string $channelSlug): void
    {
        $product = $this->registry->product($productSlug);

        if ($product === null) {
            return;
        }

        $source = $this->sources->forProduct($productSlug);

        if ($source === null) {
            return;
        }

        $upstream = $source->fetchChannel($productSlug, $channelSlug);

        if ($upstream === null) {
            return;
        }

        /** @var list<array<string, mixed>> $assetRules */
        $assetRules = $product['assets'] ?? [];
        $matches = $this->matcher->match($upstream, $assetRules);

        if ($matches === []) {
            Log::warning('Update channel warm skipped. No assets matched.', [
                'product' => $productSlug,
                'channel' => $channelSlug,
                'tag' => $upstream->tag,
            ]);

            return;
        }

        /** @var list<string> $allowedHosts */
        $allowedHosts = data_get($product, 'source.allowed_hosts', []);

        $mirrored = [];

        foreach ($matches as $match) {
            $mirroredAsset = $this->mirrorAsset(
                $productSlug,
                $upstream->version,
                $match['asset'],
                $allowedHosts,
                $match['installKind'],
                $match['rid'],
                $upstream,
            );

            if ($mirroredAsset !== null) {
                $mirrored[] = $mirroredAsset;
            }
        }

        if ($mirrored === []) {
            Log::warning('Update channel warm skipped. Asset mirror failed.', [
                'product' => $productSlug,
                'channel' => $channelSlug,
                'tag' => $upstream->tag,
            ]);

            return;
        }

        $this->writeVersionMeta($productSlug, $upstream->version, $mirrored, $upstream);

        $releaseNotesUrl = $upstream->htmlUrl !== ''
            ? $upstream->htmlUrl
            : (string) data_get($product, 'source.repo_url', '');

        $manifest = new ChannelManifest(
            product: $productSlug,
            channel: $channelSlug,
            version: $upstream->version,
            publishedAt: $upstream->publishedAt,
            releaseNotesUrl: $releaseNotesUrl,
            assets: $mirrored,
            contentHash: '',
        );

        $storage = $manifest->toStorageArray();
        $storage['contentHash'] = hash('sha256', json_encode($storage, JSON_THROW_ON_ERROR));

        $manifest = ChannelManifest::fromStorageArray($storage);

        if ($manifest === null) {
            throw new RuntimeException('Failed to build channel manifest.');
        }

        $disk = $this->disk();
        $disk->put(
            $this->channelManifestPath($productSlug, $channelSlug),
            json_encode($storage, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    private function mirrorAsset(
        string $productSlug,
        string $version,
        UpstreamAsset $asset,
        array $allowedHosts,
        string $installKind,
        string $rid,
        UpstreamRelease $release,
    ): ?MirroredAsset {
        if (! $this->urlValidator->isAllowed($asset->downloadUrl, $allowedHosts)) {
            Log::warning('Blocked upstream asset URL.', [
                'product' => $productSlug,
                'version' => $version,
                'asset' => $asset->name,
            ]);

            return null;
        }

        $disk = $this->disk();
        $relativePath = $this->assetRelativePath($productSlug, $version, $asset->name);
        $directory = "updates/{$productSlug}/{$version}";

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $expectedHash = $this->expectedSha256($release, $asset->name);

        if ($disk->exists($relativePath)) {
            $existingHash = hash_file('sha256', $disk->path($relativePath));
            $sizeBytes = $disk->size($relativePath);

            if ($expectedHash !== null && $existingHash !== $expectedHash) {
                $disk->delete($relativePath);
            } else {
                return new MirroredAsset(
                    installKind: $installKind,
                    rid: $rid,
                    filename: $asset->name,
                    sha256: $existingHash,
                    sizeBytes: $sizeBytes,
                );
            }
        }

        $tempName = $asset->name.'.tmp.'.bin2hex(random_bytes(8));
        $tempPath = "{$directory}/{$tempName}";
        $absoluteTemp = $disk->path($tempPath);

        try {
            $response = Http::timeout(300)
                ->connectTimeout(10)
                ->withOptions(['sink' => $absoluteTemp])
                ->withHeaders(['User-Agent' => 'SmelterWorks-Web'])
                ->get($asset->downloadUrl);
        } catch (ConnectionException $exception) {
            $disk->delete($tempPath);

            Log::warning('Upstream asset download failed.', [
                'product' => $productSlug,
                'asset' => $asset->name,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful() || ! $disk->exists($tempPath)) {
            $disk->delete($tempPath);

            Log::warning('Upstream asset download failed.', [
                'product' => $productSlug,
                'asset' => $asset->name,
                'status' => $response->status(),
            ]);

            return null;
        }

        $sizeBytes = $disk->size($tempPath);

        if ($sizeBytes > $this->registry->maxAssetBytes()) {
            $disk->delete($tempPath);

            Log::warning('Upstream asset exceeded size limit.', [
                'product' => $productSlug,
                'asset' => $asset->name,
                'size_bytes' => $sizeBytes,
            ]);

            return null;
        }

        $sha256 = hash_file('sha256', $absoluteTemp);

        if ($expectedHash !== null && $sha256 !== $expectedHash) {
            $disk->delete($tempPath);

            Log::warning('Upstream asset checksum mismatch.', [
                'product' => $productSlug,
                'asset' => $asset->name,
            ]);

            return null;
        }

        if ($disk->exists($relativePath)) {
            $disk->delete($relativePath);
        }

        $disk->move($tempPath, $relativePath);

        return new MirroredAsset(
            installKind: $installKind,
            rid: $rid,
            filename: $asset->name,
            sha256: $sha256,
            sizeBytes: $sizeBytes,
        );
    }

    /**
     * @param  list<MirroredAsset>  $assets
     */
    private function writeVersionMeta(
        string $productSlug,
        string $version,
        array $assets,
        UpstreamRelease $release,
    ): void {
        $meta = [
            'version' => $version,
            'tag' => $release->tag,
            'mirrored_at' => now()->toIso8601String(),
            'assets' => [],
        ];

        foreach ($assets as $asset) {
            $meta['assets'][$asset->filename] = [
                'sha256' => $asset->sha256,
                'sizeBytes' => $asset->sizeBytes,
                'installKind' => $asset->installKind,
                'rid' => $asset->rid,
            ];
        }

        $this->disk()->put(
            "updates/{$productSlug}/{$version}/.meta.json",
            json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    private function expectedSha256(UpstreamRelease $release, string $assetName): ?string
    {
        foreach ($release->assets as $asset) {
            if (strcasecmp($asset->name, 'SHA256SUMS') === 0) {
                return $this->parseSha256Sums($asset, $assetName);
            }

            if (str_ends_with(strtolower($asset->name), '.sha256')) {
                $target = substr($asset->name, 0, -7);

                if (strcasecmp($target, $assetName) === 0) {
                    return $this->fetchChecksumFile($asset->downloadUrl);
                }
            }
        }

        return null;
    }

    private function parseSha256Sums(UpstreamAsset $sumsAsset, string $assetName): ?string
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'SmelterWorks-Web'])
                ->get($sumsAsset->downloadUrl);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        foreach (preg_split('/\R/', $response->body()) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^([a-fA-F0-9]{64})\s+(.+)$/', $line, $matches) !== 1) {
                continue;
            }

            $name = trim($matches[2], '* ');

            if (strcasecmp($name, $assetName) === 0) {
                return strtolower($matches[1]);
            }
        }

        return null;
    }

    private function fetchChecksumFile(string $url): ?string
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'SmelterWorks-Web'])
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $hash = trim($response->body());

        if (preg_match('/^[a-fA-F0-9]{64}$/', $hash) === 1) {
            return strtolower($hash);
        }

        if (preg_match('/^([a-fA-F0-9]{64})/', $hash, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private function purgeOrphanVersions(string $productSlug): void
    {
        $referenced = $this->referencedVersions($productSlug);
        $disk = $this->disk();
        $productDir = "updates/{$productSlug}";

        if (! $disk->exists($productDir)) {
            return;
        }

        foreach ($disk->directories($productDir) as $directory) {
            $version = basename($directory);

            if ($version === 'channels' || in_array($version, $referenced, true)) {
                continue;
            }

            $disk->deleteDirectory($directory);
        }
    }

    private function findManifestForVersion(string $productSlug, string $version): ?ChannelManifest
    {
        $product = $this->registry->product($productSlug);

        if ($product === null) {
            return null;
        }

        foreach (array_keys($product['channels'] ?? []) as $channelSlug) {
            $manifest = $this->getChannelManifest($productSlug, (string) $channelSlug);

            if ($manifest !== null && $manifest->version === $version) {
                return $manifest;
            }
        }

        return null;
    }

    private function channelManifestPath(string $productSlug, string $channelSlug): string
    {
        return "updates/{$productSlug}/channels/{$channelSlug}.json";
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->registry->diskName());
    }
}
