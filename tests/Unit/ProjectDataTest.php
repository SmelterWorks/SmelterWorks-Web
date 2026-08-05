<?php

namespace Tests\Unit;

use App\Data\ProjectData;
use Tests\TestCase;

class ProjectDataTest extends TestCase
{
    public function test_with_page_route_clones_readonly_dto(): void
    {
        $project = new ProjectData(
            slug: 'relic',
            name: 'Relic Launcher',
            summary: 'Launcher',
            description: 'Description',
            kind: 'tool',
            status: 'active',
        );

        $updated = $project->withPageRoute('relic');

        $this->assertSame('relic', $updated->pageRoute);
        $this->assertNull($project->pageRoute);
        $this->assertNotSame($project, $updated);
    }
}
