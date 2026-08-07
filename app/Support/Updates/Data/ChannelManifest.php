<?php

namespace App\Support\Updates\Data;

final readonly class ChannelManifest
{
    /**
     * @param  list<MirroredAsset>  $assets
     */
    public function __construct(
        public string $product,
        public string $channel,
        public string $version,
        public ?string $publishedAt,
        public string $releaseNotesUrl,
        public array $assets,
        public string $contentHash,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(string $publicBaseUrl): array
    {
        $base = rtrim($publicBaseUrl, '/');

        return [
            'schemaVersion' => 1,
            'product' => $this->product,
            'channel' => $this->channel,
            'version' => $this->version,
            'publishedAt' => $this->publishedAt,
            'releaseNotesUrl' => $this->releaseNotesUrl,
            'assets' => array_map(
                fn (MirroredAsset $asset): array => [
                    'installKind' => $asset->installKind,
                    'rid' => $asset->rid,
                    'filename' => $asset->filename,
                    'url' => "{$base}/files/{$this->product}/{$this->version}/{$asset->filename}",
                    'sha256' => $asset->sha256,
                    'sizeBytes' => $asset->sizeBytes,
                ],
                $this->assets,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toStorageArray(): array
    {
        return [
            'schemaVersion' => 1,
            'product' => $this->product,
            'channel' => $this->channel,
            'version' => $this->version,
            'publishedAt' => $this->publishedAt,
            'releaseNotesUrl' => $this->releaseNotesUrl,
            'contentHash' => $this->contentHash,
            'assets' => array_map(
                fn (MirroredAsset $asset): array => [
                    'installKind' => $asset->installKind,
                    'rid' => $asset->rid,
                    'filename' => $asset->filename,
                    'sha256' => $asset->sha256,
                    'sizeBytes' => $asset->sizeBytes,
                ],
                $this->assets,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromStorageArray(array $data): ?self
    {
        $product = (string) ($data['product'] ?? '');
        $channel = (string) ($data['channel'] ?? '');
        $version = (string) ($data['version'] ?? '');

        if ($product === '' || $channel === '' || $version === '') {
            return null;
        }

        $assets = [];
        foreach ($data['assets'] ?? [] as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $filename = (string) ($asset['filename'] ?? '');

            if ($filename === '') {
                continue;
            }

            $assets[] = new MirroredAsset(
                installKind: (string) ($asset['installKind'] ?? ''),
                rid: (string) ($asset['rid'] ?? ''),
                filename: $filename,
                sha256: (string) ($asset['sha256'] ?? ''),
                sizeBytes: (int) ($asset['sizeBytes'] ?? 0),
            );
        }

        return new self(
            product: $product,
            channel: $channel,
            version: $version,
            publishedAt: isset($data['publishedAt']) ? (string) $data['publishedAt'] : null,
            releaseNotesUrl: (string) ($data['releaseNotesUrl'] ?? ''),
            assets: $assets,
            contentHash: (string) ($data['contentHash'] ?? ''),
        );
    }
}
