<?php

namespace App\Http\Middleware;

use App\Support\Metrics\MetricsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordMetrics
{
    public function __construct(
        private readonly MetricsRecorder $metrics,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('metrics.enabled', false)) {
            return $next($request);
        }

        $startedAt = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startedAt;
        $route = $request->route();
        $routeName = $route?->getName();
        $routeLabel = $this->metrics->resolveRouteLabel(
            is_string($routeName) ? $routeName : null,
            '/'.ltrim($request->path(), '/'),
            $response->getStatusCode(),
        );

        $this->metrics->recordHttpRequest(
            $request->method(),
            $routeLabel,
            $response->getStatusCode(),
            $duration,
        );

        return $response;
    }
}
