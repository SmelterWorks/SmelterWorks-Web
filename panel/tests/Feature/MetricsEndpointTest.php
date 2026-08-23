<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'metrics.enabled' => true,
            'metrics.token' => 'test-metrics-token',
            'metrics.route' => '/metrics',
        ]);
    }

    public function test_metrics_endpoint_is_hidden_without_configured_token(): void
    {
        config([
            'metrics.token' => '',
        ]);

        $this->get('/metrics')->assertNotFound();
    }

    public function test_metrics_endpoint_rejects_missing_token_when_configured(): void
    {
        $this->get('/metrics')->assertUnauthorized();
    }

    public function test_metrics_endpoint_rejects_invalid_token(): void
    {
        $this->get('/metrics?token=wrong')->assertUnauthorized();
    }

    public function test_metrics_endpoint_returns_prometheus_payload_with_valid_token(): void
    {
        $this->withToken('test-metrics-token')
            ->get('/metrics')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        $this->get('/metrics?token=test-metrics-token')
            ->assertOk();
    }

    public function test_metrics_middleware_records_request_after_health_check(): void
    {
        $this->withToken('test-metrics-token')->get('/up')->assertOk();

        $response = $this->withToken('test-metrics-token')->get('/metrics');

        $response->assertOk();
        $this->assertStringContainsString('http_requests_total', (string) $response->getContent());
    }
}
