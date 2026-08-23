<?php

namespace App\Support\Security;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class LoginSecurityService
{
    public function __construct(
        private readonly SessionFingerprint $fingerprint,
    ) {}

    public function ensureCanAttempt(Request $request, ?User $user): void
    {
        $ipKey = 'panel-login-ip:'.$request->ip();
        $max = (int) config('panel.security.login_max_attempts', 5);

        if (RateLimiter::tooManyAttempts($ipKey, $max * 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts from this network. Try again later.',
            ]);
        }

        if ($user !== null && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => 'This account is temporarily locked.',
            ]);
        }

        if ($user !== null) {
            $accountKey = 'panel-login-user:'.$user->id;

            if (RateLimiter::tooManyAttempts($accountKey, $max)) {
                $user->update([
                    'locked_until' => now()->addMinutes((int) config('panel.security.lockout_minutes', 30)),
                ]);

                $this->record($user, 'account_locked', $request);

                throw ValidationException::withMessages([
                    'email' => 'This account is temporarily locked.',
                ]);
            }
        }
    }

    public function recordFailure(?User $user, Request $request): void
    {
        $decay = (int) config('panel.security.login_decay_minutes', 15) * 60;
        RateLimiter::hit('panel-login-ip:'.$request->ip(), $decay);

        if ($user !== null) {
            RateLimiter::hit('panel-login-user:'.$user->id, $decay);
            $user->increment('failed_login_count');
            $this->record($user, 'login_failed', $request);
        } else {
            SecurityEvent::query()->create([
                'event' => 'login_failed_unknown',
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }
    }

    public function recordSuccess(User $user, Request $request): void
    {
        RateLimiter::clear('panel-login-ip:'.$request->ip());
        RateLimiter::clear('panel-login-user:'.$user->id);

        $user->update([
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);

        $request->session()->regenerate();

        UserSession::query()->updateOrCreate(
            ['session_id' => $request->session()->getId()],
            [
                'user_id' => $user->id,
                'ip_subnet' => $this->fingerprint->ipSubnet($request->ip()),
                'user_agent_family' => $this->fingerprint->userAgentFamily((string) $request->userAgent()),
                'last_activity_at' => now(),
                'step_up_verified_at' => now(),
                'revoked' => false,
            ],
        );

        $this->record($user, 'login_success', $request);
    }

    public function requiresStepUp(User $user, Request $request): bool
    {
        $session = UserSession::query()
            ->where('session_id', $request->session()->getId())
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->first();

        if ($session === null) {
            return true;
        }

        $currentSubnet = $this->fingerprint->ipSubnet($request->ip());
        $currentAgent = $this->fingerprint->userAgentFamily((string) $request->userAgent());

        if ($session->ip_subnet !== $currentSubnet || $session->user_agent_family !== $currentAgent) {
            $this->record($user, 'session_anomaly', $request, [
                'previous_subnet' => $session->ip_subnet,
                'previous_agent' => $session->user_agent_family,
            ]);

            return true;
        }

        $verifiedAt = $session->step_up_verified_at;

        if ($verifiedAt === null) {
            return true;
        }

        return $verifiedAt->lt(now()->subMinutes((int) config('panel.security.step_up_minutes', 15)));
    }

    public function verifyStepUp(User $user, Request $request): void
    {
        UserSession::query()
            ->where('session_id', $request->session()->getId())
            ->where('user_id', $user->id)
            ->update([
                'step_up_verified_at' => now(),
                'ip_subnet' => $this->fingerprint->ipSubnet($request->ip()),
                'user_agent_family' => $this->fingerprint->userAgentFamily((string) $request->userAgent()),
                'last_activity_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function record(User $user, string $event, Request $request, ?array $metadata = null): void
    {
        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
