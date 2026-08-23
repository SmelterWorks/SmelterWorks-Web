<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'missing_token'], 401);
        }

        $plain = substr($header, 7);
        $token = ApiToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($token === null) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $token->update(['last_used_at' => now()]);
        Auth::login($token->user);
        $request->attributes->set('api_token_abilities', $token->abilities ?? []);

        return $next($request);
    }
}
