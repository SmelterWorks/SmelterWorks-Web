<?php

namespace Tests\Unit;

use App\Support\Content\ProjectCatalog;
use Tests\TestCase;

class ProjectCatalogTest extends TestCase
{
    public function test_mods_only_includes_active_mod_projects(): void
    {
        $mods = app(ProjectCatalog::class)->mods();

        $this->assertTrue($mods->every(fn ($project): bool => $project->kind === 'mod'));
        $this->assertTrue($mods->every(fn ($project): bool => $project->status === 'active'));
        $this->assertTrue($mods->contains(fn ($project): bool => $project->slug === 'better-sprinting'));
    }

    public function test_catalog_does_not_include_placeholder_projects(): void
    {
        $names = app(ProjectCatalog::class)->all()->pluck('name')->all();

        $this->assertNotContains('Forge Kit', $names);
        $this->assertNotContains('Slag Lib', $names);
        $this->assertNotContains('Hearthside', $names);
    }
}
