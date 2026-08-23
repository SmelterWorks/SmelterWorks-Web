<?php

namespace App\Http\Controllers;

use App\Models\Subuser;
use App\Models\User;
use App\Support\Permissions\OrganizationAccess;
use App\Support\Permissions\SubuserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SubuserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManage($request);

        return view('subusers.index', [
            'subusers' => $request->user()->organization?->subusers()->with('user')->get() ?? collect(),
            'permissions' => SubuserPermission::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', SubuserPermission::all())],
        ]);

        $organization = $request->user()->organization;
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => strstr($validated['email'], '@', true) ?: $validated['email'],
                'email' => $validated['email'],
                'password' => Hash::make(bin2hex(random_bytes(24))),
                'organization_id' => $organization->id,
                'role' => 'subuser',
            ]);
        } else {
            abort_unless($user->organization_id === null || $user->organization_id === $organization->id, 422);
            $user->update([
                'organization_id' => $organization->id,
                'role' => 'subuser',
            ]);
        }

        Subuser::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            ['permissions' => $validated['permissions']],
        );

        return back()->with('status', 'Subuser access updated.');
    }

    public function destroy(Request $request, Subuser $subuser): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($subuser->organization_id === $request->user()->organization_id, 403);

        $subuser->delete();

        return back()->with('status', 'Subuser removed.');
    }

    private function authorizeManage(Request $request): void
    {
        app(OrganizationAccess::class)->authorize($request, SubuserPermission::MANAGE_SUBUSERS);
    }
}
