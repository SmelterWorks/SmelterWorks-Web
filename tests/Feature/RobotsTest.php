<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsTest extends TestCase
{
    public function test_robots_disallows_update_endpoints(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /updates/', false)
            ->assertSee('Disallow: /files/', false);
    }
}
