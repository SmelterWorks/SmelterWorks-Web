<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureManagedMode;
use App\Http\Middleware\RecordMetrics;
use App\Http\Middleware\RequireApiAbility;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyMetricsToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
        $middleware->append(RecordMetrics::class);
        $middleware->alias([
            'auth.api' => AuthenticateApiToken::class,
            'api.ability' => RequireApiAbility::class,
            'managed' => EnsureManagedMode::class,
            'metrics.token' => VerifyMetricsToken::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->booting(function (): void {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('agent', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    })
    ->create();
