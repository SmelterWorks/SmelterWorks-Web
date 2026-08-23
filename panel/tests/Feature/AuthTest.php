<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'organization_name' => 'Alex Org',
            'email' => 'alex@example.test',
            'password' => 'StrongPass!123',
            'password_confirmation' => 'StrongPass!123',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'alex@example.test',
            'password' => 'StrongPass!123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'organization_name' => 'Alex Org',
            'email' => 'alex2@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
