<?php

namespace App\Support\Permissions;

use App\Models\Subuser;
use App\Models\User;
use Illuminate\Http\Request;

final class OrganizationAccess
{
    public function can(User $user, string $permission): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        $subuser = Subuser::query()
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->first();

        return $subuser?->can($permission) ?? false;
    }

    public function authorize(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless($user !== null, 403);
        abort_unless($this->can($user, $permission), 403);
    }
}
