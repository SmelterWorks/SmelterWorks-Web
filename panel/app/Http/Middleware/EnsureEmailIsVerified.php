<?php

namespace App\Http\Middleware;

use App\Support\Mail\MailConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! MailConfig::verificationEnabled()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                abort(403, 'Your email address is not verified.');
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
