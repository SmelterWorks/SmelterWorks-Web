<?php

namespace App\Support\Updates\Sources;

use App\Support\Updates\Data\UpstreamRelease;

interface UpdateSource
{
    public function fetchChannel(string $productSlug, string $channelSlug): ?UpstreamRelease;
}
