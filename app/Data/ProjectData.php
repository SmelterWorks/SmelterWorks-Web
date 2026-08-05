<?php

namespace App\Data;

readonly class ProjectData
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $summary,
        public string $description,
        public string $kind,
        public string $status,
        public ?string $repoUrl = null,
        public ?string $modDbUrl = null,
        public ?string $pageRoute = null,
        public array $tags = [],
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            slug: (string) $attributes['slug'],
            name: (string) $attributes['name'],
            summary: (string) $attributes['summary'],
            description: (string) $attributes['description'],
            kind: (string) $attributes['kind'],
            status: (string) $attributes['status'],
            repoUrl: isset($attributes['repo_url']) ? (string) $attributes['repo_url'] : null,
            modDbUrl: isset($attributes['mod_db_url']) ? (string) $attributes['mod_db_url'] : null,
            pageRoute: isset($attributes['page_route']) ? (string) $attributes['page_route'] : null,
            tags: array_values($attributes['tags'] ?? []),
        );
    }

    public function withPageRoute(string $pageRoute): self
    {
        return clone ($this, ['pageRoute' => $pageRoute]);
    }

    public function url(): string
    {
        if (filled($this->pageRoute)) {
            return route($this->pageRoute);
        }

        return route('projects.show', $this->slug);
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'mod' => 'Mod',
            'tool' => 'Tool',
            'library' => 'Library',
            default => ucfirst($this->kind),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'planned' => 'Planned',
            'archived' => 'Archived',
            default => ucfirst($this->status),
        };
    }
}
