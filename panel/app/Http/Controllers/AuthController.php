<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use App\Support\Altcha\AltchaService;
use App\Support\Mail\MailConfig;
use App\Support\Security\LoginSecurityService;
use App\Support\Security\PasswordPolicy;
use App\Support\Security\PasswordStrengthValidator;
use App\Support\Security\RegisterSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(AltchaService $altcha): View
    {
        return view('auth.login', [
            'altchaChallengeUrl' => $altcha->widgetChallengeUrl(),
        ]);
    }

    public function login(LoginRequest $request, LoginSecurityService $security): RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();
        $security->ensureCanAttempt($request, $user);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $security->recordFailure($user, $request);

            $message = 'Invalid credentials.';
            $remaining = $security->remainingAttempts($user, $request);

            if ($remaining !== null && $remaining <= 2) {
                $message .= ' '.$remaining.' attempt'.($remaining === 1 ? '' : 's').' remaining before lockout.';
            }

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        /** @var User $authenticated */
        $authenticated = Auth::user();
        $security->recordSuccess($authenticated, $request);

        if ($authenticated->totp_enabled) {
            Auth::logout();
            $request->session()->put('totp_user_id', $authenticated->id);

            return redirect()->route('totp.challenge');
        }

        if (MailConfig::verificationEnabled() && ! $authenticated->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(AltchaService $altcha, PasswordPolicy $policy): View
    {
        return view('auth.register', [
            'altchaChallengeUrl' => $altcha->widgetChallengeUrl(),
            'passwordPolicy' => $policy->requirements(),
            'passwordSummary' => $policy->summary(),
        ]);
    }

    public function register(
        RegisterRequest $request,
        PasswordStrengthValidator $passwords,
        RegisterSecurityService $registerSecurity,
        PasswordPolicy $policy,
    ): RedirectResponse {
        $registerSecurity->ensureCanAttempt($request);

        try {
            $passwords->validate($request->string('password')->toString());
        } catch (ValidationException $exception) {
            $registerSecurity->recordFailure($request);

            throw $exception;
        }

        $teamName = trim($request->string('team_name')->toString());
        $displayName = $request->string('name')->toString();
        $organizationName = $teamName !== '' ? $teamName : "{$displayName}'s workspace";

        $organization = Organization::query()->create([
            'name' => $organizationName,
            'slug' => Str::slug($organizationName).'-'.Str::lower(Str::random(4)),
        ]);

        $user = User::query()->create([
            'name' => $displayName,
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'organization_id' => $organization->id,
            'role' => 'owner',
            'password_changed_at' => now(),
            'email_verified_at' => MailConfig::verificationEnabled() ? null : now(),
        ]);

        $registerSecurity->recordSuccess($request);

        Auth::login($user);
        $request->session()->regenerate();

        if (MailConfig::verificationEnabled()) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
