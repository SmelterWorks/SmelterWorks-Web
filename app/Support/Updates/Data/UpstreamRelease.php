<?php

namespace App\Support\Updates\Data;

final readonly class UpstreamRelease
{
    /**
     * @param  list<UpstreamAsset>  $assets
     */
    public function __construct(
        public string $tag,
        public string $version,
        public string $htmlUrl,
        public ?string $publishedAt,
        public array $assets,
    ) {}
}
