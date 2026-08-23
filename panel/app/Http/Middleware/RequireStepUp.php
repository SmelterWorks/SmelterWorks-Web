<?php

namespace App\Http\Middleware;

use App\Support\Security\LoginSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStepUp
{
    public function __construct(
        private readonly LoginSecurityService $security,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($this->security->requiresStepUp($user, $request)) {
            if ($request->filled('password')) {
                if (! auth()->validate([
                    'email' => $user->email,
                    'password' => $request->string('password'),
                ])) {
                    return back()->withErrors(['password' => 'Password confirmation failed.']);
                }

                $this->security->verifyStepUp($user, $request);
            } else {
                return response()->view('auth.step-up', [
                    'intended' => $request->fullUrl(),
                ], 403);
            }
        }

        return $next($request);
    }
}
