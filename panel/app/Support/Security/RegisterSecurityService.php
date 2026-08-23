<?php

namespace App\Support\Security;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class RegisterSecurityService
{
    public function ensureCanAttempt(Request $request): void
    {
        $ipKey = 'panel-register-ip:'.$request->ip();
        $max = (int) config('panel.security.register_max_attempts', 5);

        if (RateLimiter::tooManyAttempts($ipKey, $max)) {
            $seconds = RateLimiter::availableIn($ipKey);

            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Try again in '.ceil($seconds / 60).' minutes.',
            ]);
        }
    }

    public function recordFailure(Request $request): void
    {
        $decay = (int) config('panel.security.register_decay_minutes', 15) * 60;
        RateLimiter::hit('panel-register-ip:'.$request->ip(), $decay);

        SecurityEvent::query()->create([
            'event' => 'register_failed',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    public function recordSuccess(Request $request): void
    {
        RateLimiter::clear('panel-register-ip:'.$request->ip());
    }
}
