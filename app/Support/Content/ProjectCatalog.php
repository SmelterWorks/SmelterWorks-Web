<?php

namespace App\Support\Content;

use App\Data\ProjectData;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectCatalog
{
    /**
     * @return Collection<int, ProjectData>
     */
    public function all(): Collection
    {
        /** @var list<array<string, mixed>> $projects */
        $projects = config('smelterworks.projects', []);

        return collect($projects)
            ->map(fn (array $project): ProjectData => ProjectData::fromArray($project))
            ->values();
    }

    public function find(string $slug): ?ProjectData
    {
        return $this->all()->first(
            fn (ProjectData $project): bool => $project->slug === $slug
        );
    }

    public function findOrFail(string $slug): ProjectData
    {
        $project = $this->find($slug);

        if ($project === null) {
            throw new NotFoundHttpException("Project [{$slug}] was not found.");
        }

        return $project;
    }

    /**
     * @return Collection<int, ProjectData>
     */
    public function featured(int $limit = 3): Collection
    {
        return $this->all()->take($limit);
    }

    /**
     * @return Collection<int, ProjectData>
     */
    public function mods(): Collection
    {
        return $this->all()
            ->filter(fn (ProjectData $project): bool => $project->kind === 'mod' && $project->status === 'active')
            ->values();
    }
}
