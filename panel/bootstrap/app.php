<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->alias([
            'auth.api' => AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->booting(function (): void {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('agent', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    })
    ->create();
