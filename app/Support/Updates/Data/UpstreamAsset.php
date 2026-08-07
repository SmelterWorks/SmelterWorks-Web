<?php

namespace App\Support\Updates\Data;

final readonly class UpstreamAsset
{
    public function __construct(
        public string $name,
        public string $downloadUrl,
    ) {}
}
