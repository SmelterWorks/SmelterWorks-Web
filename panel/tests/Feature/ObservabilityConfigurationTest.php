<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservabilityConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sentry_configuration_is_available(): void
    {
        $this->assertIsArray(config('sentry'));
        $this->assertArrayHasKey('dsn', config('sentry'));
        $this->assertContains('/metrics', config('sentry.ignore_transactions'));
    }

    public function test_metrics_configuration_is_available(): void
    {
        $this->assertIsArray(config('metrics'));
        $this->assertSame('/metrics', config('metrics.route'));
    }

    public function test_panel_doctor_passes_with_sqlite_memory_database(): void
    {
        $this->artisan('panel:doctor')
            ->assertSuccessful()
            ->expectsOutputToContain('Database connection is reachable.');
    }
}
