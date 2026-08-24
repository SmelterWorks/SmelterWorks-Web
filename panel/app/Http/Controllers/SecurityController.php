<?php

namespace App\Http\Controllers;

use App\Support\Security\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function show(Request $request): View
    {
        return view('settings.security', [
            'enabled' => (bool) $request->user()->totp_enabled,
            'recoveryCodes' => $request->session()->get('totp_recovery_codes'),
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

        $codes = $totp->recoveryCodes();
        $hashed = array_map(fn (string $code): string => Hash::make($code), $codes);

        $request->user()->update([
            'totp_secret' => $secret,
            'totp_enabled' => true,
            'totp_recovery_codes' => $hashed,
        ]);

        return back()->with('totp_recovery_codes', $codes)->with('status', 'Two-factor authentication enabled.');
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
            'totp_recovery_codes' => null,
        ]);

        return back()->with('status', 'Two-factor authentication disabled.');
    }
}
