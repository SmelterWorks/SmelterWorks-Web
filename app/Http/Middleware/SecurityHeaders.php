<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $scriptSrc = "'self'";
        $styleSrc = "'self' 'unsafe-inline'";
        $fontSrc = "'self' data:";
        $connectSrc = "'self'";

        if (Vite::isRunningHot()) {
            $viteOrigin = rtrim((string) file_get_contents(Vite::hotFile()));
            $wsOrigin = preg_replace('/^http/', 'ws', $viteOrigin);
            $viteSources = trim($viteOrigin.' '.$wsOrigin);

            $scriptSrc .= ' '.$viteSources;
            $styleSrc .= ' '.$viteOrigin;
            $fontSrc .= ' '.$viteOrigin;
            $connectSrc .= ' '.$viteSources;
        }

        return "default-src 'self'; "
            ."base-uri 'self'; "
            ."form-action 'self'; "
            ."frame-ancestors 'none'; "
            ."object-src 'none'; "
            ."script-src {$scriptSrc}; "
            ."style-src {$styleSrc}; "
            ."font-src {$fontSrc}; "
            ."img-src 'self' data: https:; "
            ."connect-src {$connectSrc}";
    }
}
