<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.index', [
            'organization' => $request->user()->organization,
            'user' => $request->user(),
        ]);
    }

    public function updateOrganization(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'billing_email' => ['nullable', 'email', 'max:255'],
        ]);

        $request->user()->organization?->update($validated);

        return back()->with('status', 'Team settings saved.');
    }
}
