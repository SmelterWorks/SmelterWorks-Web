<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_without_team_name(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex@example.test',
            'password' => 'panel8ok',
            'password_confirmation' => 'panel8ok',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'alex@example.test',
            'password' => 'panel8ok',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_user_can_register_with_optional_team_name(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'team_name' => 'Crew Alpha',
            'email' => 'alex-team@example.test',
            'password' => 'panel8ok',
            'password_confirmation' => 'panel8ok',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Crew Alpha',
        ]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Alex',
            'email' => 'alex2@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_email_verification_is_required_when_smtp_and_flag_enabled(): void
    {
        Notification::fake();

        config([
            'panel.verify_email' => true,
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
        ]);

        $this->post('/register', [
            'name' => 'Pat',
            'email' => 'pat@example.test',
            'password' => 'panel8ok',
            'password_confirmation' => 'panel8ok',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'pat@example.test')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }
}
