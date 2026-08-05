<?php

namespace App\Support\Seo;

use App\Data\ProjectData;
use App\Support\Content\ProjectCatalog;

final class SitemapBuilder
{
    public function __construct(
        private readonly ProjectCatalog $projects,
    ) {}

    /**
     * @return list<array{loc: string, changefreq: string, priority: string}>
     */
    public function urls(): array
    {
        $entries = [
            $this->entry(route('home'), 'weekly', '1.0'),
            $this->entry(route('hosting'), 'weekly', '0.9'),
            $this->entry(route('relic'), 'weekly', '0.9'),
            $this->entry(route('relic.download'), 'weekly', '0.8'),
            $this->entry(route('mods'), 'weekly', '0.8'),
            $this->entry(route('projects.index'), 'weekly', '0.8'),
            $this->entry(route('about'), 'monthly', '0.6'),
            $this->entry(route('branding'), 'monthly', '0.5'),
            $this->entry(route('contact'), 'monthly', '0.5'),
            $this->entry(route('donate'), 'monthly', '0.5'),
            $this->entry(route('contribute'), 'monthly', '0.5'),
            $this->entry(route('privacy'), 'yearly', '0.3'),
            $this->entry(route('terms'), 'yearly', '0.3'),
        ];

        if (! filled(config('smelterworks.links.panel'))) {
            $entries[] = $this->entry(route('panel'), 'monthly', '0.4');
        }

        $projectUrls = $this->projects->all()
            ->map(fn (ProjectData $project): string => $project->url())
            ->unique()
            ->values();

        foreach ($projectUrls as $url) {
            $entries[] = $this->entry($url, 'weekly', '0.7');
        }

        $seen = [];
        $unique = [];

        foreach ($entries as $entry) {
            if (isset($seen[$entry['loc']])) {
                continue;
            }

            $seen[$entry['loc']] = true;
            $unique[] = $entry;
        }

        return $unique;
    }

    /**
     * @return array{loc: string, changefreq: string, priority: string}
     */
    private function entry(string $loc, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
