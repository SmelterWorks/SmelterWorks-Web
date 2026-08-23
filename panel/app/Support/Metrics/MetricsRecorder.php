<?php

namespace App\Support\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class MetricsRecorder
{
    private readonly string $namespace;

    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {
        $this->namespace = (string) config('metrics.namespace', 'smelterworks_panel');
    }

    public function recordHttpRequest(string $method, string $route, int $statusCode, float $durationSeconds): void
    {
        $labels = [
            'method' => strtoupper($method),
            'route' => $route,
            'status' => (string) $statusCode,
        ];

        $this->httpRequestsTotal()->inc($labels);
        $this->httpRequestDurationSeconds()->observe($durationSeconds, $labels);
    }

    public function setAppInfo(string $version, string $mode, string $databaseDriver): void
    {
        $this->appInfo()->set(1, [
            'version' => $version,
            'mode' => $mode,
            'database_driver' => $databaseDriver,
        ]);
    }

    public function setQueueDepth(int $depth): void
    {
        $this->queueDepth()->set($depth);
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat;

        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function contentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }

    private function httpRequestsTotal(): Counter
    {
        return $this->registry->getOrRegisterCounter(
            $this->namespace,
            'http_requests_total',
            'Total HTTP requests handled by the panel.',
            ['method', 'route', 'status'],
        );
    }

    private function httpRequestDurationSeconds(): Histogram
    {
        return $this->registry->getOrRegisterHistogram(
            $this->namespace,
            'http_request_duration_seconds',
            'HTTP request duration in seconds.',
            ['method', 'route', 'status'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        );
    }

    private function appInfo(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            'app_info',
            'Panel build and runtime metadata.',
            ['version', 'mode', 'database_driver'],
        );
    }

    private function queueDepth(): Gauge
    {
        return $this->registry->getOrRegisterGauge(
            $this->namespace,
            'queue_depth',
            'Pending jobs in the default queue.',
        );
    }

    public function resolveRouteLabel(?string $routeName, string $path, int $statusCode): string
    {
        if ($routeName !== null && $routeName !== '') {
            return $routeName;
        }

        if ($statusCode === Response::HTTP_NOT_FOUND) {
            return 'not_found';
        }

        return $path === '' ? '/' : $path;
    }
}
