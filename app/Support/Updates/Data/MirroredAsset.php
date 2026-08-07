<?php

namespace App\Support\Updates\Data;

final readonly class MirroredAsset
{
    public function __construct(
        public string $installKind,
        public string $rid,
        public string $filename,
        public string $sha256,
        public int $sizeBytes,
    ) {}
}
