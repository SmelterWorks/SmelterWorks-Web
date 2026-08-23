<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetricsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('metrics.token', '');

        if ($token === '') {
            abort(Response::HTTP_NOT_FOUND);
        }

        $provided = $request->bearerToken() ?? $request->query('token');

        if (! is_string($provided) || ! hash_equals($token, $provided)) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
