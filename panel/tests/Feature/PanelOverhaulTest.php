<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelOverhaulTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile_and_purchase_pages(): void
    {
        $org = Organization::query()->create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::factory()->create(['role' => 'owner', 'organization_id' => $org->id]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Change password');

        $this->actingAs($user)
            ->get(route('servers.purchase'))
            ->assertOk()
            ->assertSee('Configuration');
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
