<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiAbility
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $tokenAbilities = $request->attributes->get('api_token_abilities', []);

        if (! is_array($tokenAbilities)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        foreach ($abilities as $ability) {
            if (! in_array($ability, $tokenAbilities, true)) {
                return response()->json(['error' => 'missing_ability', 'ability' => $ability], 403);
            }
        }

        return $next($request);
    }
}
