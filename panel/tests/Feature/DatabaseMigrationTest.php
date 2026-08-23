<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_panel_tables_exist_after_migrations(): void
    {
        $tables = [
            'users',
            'organizations',
            'game_servers',
            'daemon_registrations',
            'security_events',
            'jobs',
            'cache',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}]");
        }
    }
}
