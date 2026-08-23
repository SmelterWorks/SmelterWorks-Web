<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use App\Support\Security\LoginSecurityService;
use App\Support\Security\PasswordStrengthValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, LoginSecurityService $security): RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();
        $security->ensureCanAttempt($request, $user);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $security->recordFailure($user, $request);

            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        /** @var User $authenticated */
        $authenticated = Auth::user();
        $security->recordSuccess($authenticated, $request);

        if ($authenticated->totp_enabled) {
            Auth::logout();
            $request->session()->put('totp_user_id', $authenticated->id);

            return redirect()->route('totp.challenge');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(PasswordStrengthValidator $passwords): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, PasswordStrengthValidator $passwords): RedirectResponse
    {
        $passwords->validate($request->string('password')->toString());

        $organization = Organization::query()->create([
            'name' => $request->string('organization_name'),
            'slug' => Str::slug($request->string('organization_name')).'-'.Str::lower(Str::random(4)),
        ]);

        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'organization_id' => $organization->id,
            'role' => 'owner',
            'password_changed_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

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
