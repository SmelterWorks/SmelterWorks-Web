<?php

namespace App\Support\Updates\Sources;

use App\Support\Updates\UpdateProductRegistry;

final class UpdateSourceResolver
{
    public function __construct(
        private readonly UpdateProductRegistry $registry,
        private readonly GitHubReleaseSource $githubSource,
    ) {}

    public function forProduct(string $productSlug): ?UpdateSource
    {
        $product = $this->registry->product($productSlug);

        if ($product === null) {
            return null;
        }

        $driver = (string) data_get($product, 'source.driver', 'github');

        if ($driver === 'github' || $driver === 'forgejo') {
            return $this->githubSource;
        }

        return null;
    }
}
