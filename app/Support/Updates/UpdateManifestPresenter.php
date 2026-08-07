<?php

namespace App\Support\Updates;

use App\Support\Updates\Data\ChannelManifest;

final class UpdateManifestPresenter
{
    public function __construct(
        private readonly UpdateProductRegistry $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(ChannelManifest $manifest): array
    {
        return $manifest->toPublicArray($this->registry->publicBaseUrl());
    }

    public function etag(ChannelManifest $manifest): string
    {
        $hash = $manifest->contentHash;

        if ($hash === '') {
            $hash = hash('sha256', json_encode($this->present($manifest), JSON_THROW_ON_ERROR));
        }

        return '"'.$hash.'"';
    }
}
