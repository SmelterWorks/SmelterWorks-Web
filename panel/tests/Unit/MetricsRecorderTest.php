<?php

namespace Tests\Unit;

use App\Support\Metrics\MetricsRecorder;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Tests\TestCase;

class MetricsRecorderTest extends TestCase
{
    public function test_records_http_metrics_in_prometheus_format(): void
    {
        config([
            'metrics.namespace' => 'test_panel',
        ]);

        $recorder = new MetricsRecorder(new CollectorRegistry(new InMemory));

        $recorder->recordHttpRequest('GET', 'dashboard', 200, 0.12);
        $recorder->setAppInfo('test', 'managed', 'sqlite');

        $output = $recorder->render();

        $this->assertStringContainsString('test_panel_http_requests_total', $output);
        $this->assertStringContainsString('test_panel_http_request_duration_seconds', $output);
        $this->assertStringContainsString('test_panel_app_info', $output);
    }

    public function test_resolve_route_label_prefers_named_route(): void
    {
        $recorder = new MetricsRecorder(new CollectorRegistry(new InMemory));

        $this->assertSame('dashboard', $recorder->resolveRouteLabel('dashboard', '/ignored', 200));
        $this->assertSame('not_found', $recorder->resolveRouteLabel(null, '/missing', 404));
        $this->assertSame('/health', $recorder->resolveRouteLabel(null, '/health', 200));
    }
}
