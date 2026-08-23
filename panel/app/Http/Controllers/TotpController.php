<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Security\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TotpController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.totp', [
            'enabled' => (bool) $request->user()->totp_enabled,
        ]);
    }

    public function begin(Request $request, TotpService $totp): RedirectResponse
    {
        $secret = $totp->generateSecret();
        $request->session()->put('totp_pending_secret', $secret);

        return back()->with([
            'totp_secret' => $secret,
            'totp_uri' => $totp->provisioningUri($request->user(), $secret),
        ]);
    }

    public function confirm(Request $request, TotpService $totp): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $secret = (string) $request->session()->pull('totp_pending_secret', '');

        if ($secret === '' || ! $totp->verifyPending($request->user(), $secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.']);
        }

        $request->user()->update([
            'totp_secret' => $secret,
            'totp_enabled' => true,
        ]);

        return back()->with('status', 'Two-factor authentication enabled.');
    }

    public function disable(Request $request, TotpService $totp): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $totp->verify($request->user(), $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.']);
        }

        $request->user()->update([
            'totp_secret' => null,
            'totp_enabled' => false,
        ]);

        return back()->with('status', 'Two-factor authentication disabled.');
    }

    public function challenge(): View
    {
        return view('auth.totp-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var User|null $user */
        $user = User::query()->find($request->session()->get('totp_user_id'));

        if ($user === null || ! $totp->verify($user, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.']);
        }

        $request->session()->forget('totp_user_id');
        $request->session()->put('totp_passed_at', now()->timestamp);
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
