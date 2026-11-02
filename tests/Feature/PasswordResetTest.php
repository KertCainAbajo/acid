<?php

namespace Tests\Feature;

use App\Http\Controllers\PasswordResetController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sign_in_page_links_to_a_working_forgot_password_page(): void
    {
        $this->get('/login')->assertSee(route('password.request'));

        $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?');
    }

    public function test_reset_link_is_emailed_without_revealing_which_emails_have_accounts(): void
    {
        Notification::fake();
        $patient = User::factory()->create(['email' => 'patient@example.com']);
        $deactivated = User::factory()->create(['email' => 'off@example.com', 'is_active' => false]);

        $this->post('/forgot-password', ['email' => 'patient@example.com'])->assertSessionHas('status', PasswordResetController::LINK_SENT_MESSAGE);
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status', PasswordResetController::LINK_SENT_MESSAGE);
        $this->post('/forgot-password', ['email' => 'off@example.com'])->assertSessionHas('status', PasswordResetController::LINK_SENT_MESSAGE);

        Notification::assertSentTo($patient, ResetPassword::class);
        Notification::assertNotSentTo($deactivated, ResetPassword::class);
        $this->assertDatabaseHas('system_logs', ['action' => 'password_reset_requested', 'target_id' => $patient->id]);
    }

    public function test_a_mail_provider_failure_is_logged_instead_of_showing_an_error_page(): void
    {
        $patient = User::factory()->create(['email' => 'patient@example.com']);
        Password::shouldReceive('sendResetLink')->andThrow(new \RuntimeException('SMTP authentication failed'));

        $this->post('/forgot-password', ['email' => 'patient@example.com'])->assertSessionHas('status', PasswordResetController::LINK_SENT_MESSAGE);

        $this->assertDatabaseHas('system_logs', ['action' => 'password_reset_requested', 'target_id' => $patient->id]);
    }

    public function test_patient_can_reset_their_password_and_is_signed_out_everywhere(): void
    {
        $patient = User::factory()->create(['email' => 'patient@example.com', 'failed_login_attempts' => 9, 'locked_until' => now()->addMinutes(10)]);
        $patient->createToken('old-phone');
        $token = Password::createToken($patient);

        $this->get("/reset-password/{$token}?email=patient@example.com")->assertOk()->assertSee('Choose a new password');
        $response = $this->post('/reset-password', ['token' => $token, 'email' => 'patient@example.com', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Your password has been reset. Sign in with your new password.');
        $this->assertGuest();
        $patient->refresh();
        $this->assertFalse($patient->isLocked());
        $this->assertNotNull($patient->password_changed_at);
        $this->assertSame(0, $patient->tokens()->count());
        $this->assertDatabaseHas('system_logs', ['action' => 'password_reset_completed', 'target_id' => $patient->id]);

        $this->post('/login', ['email' => 'patient@example.com', 'password' => 'NewSecret123'])->assertRedirect(route('dashboard'));
    }

    public function test_reset_links_work_only_once_and_bad_tokens_are_rejected(): void
    {
        $patient = User::factory()->create(['email' => 'patient@example.com']);
        $token = Password::createToken($patient);
        $payload = ['token' => $token, 'email' => 'patient@example.com', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123'];

        $this->post('/reset-password', [...$payload, 'token' => 'forged-token'])->assertSessionHasErrors('email');
        $this->post('/reset-password', $payload)->assertRedirect(route('login'));
        $this->post('/reset-password', [...$payload, 'password' => 'Another123', 'password_confirmation' => 'Another123'])->assertSessionHasErrors('email');

        $this->post('/login', ['email' => 'patient@example.com', 'password' => 'NewSecret123'])->assertRedirect(route('dashboard'));
    }

    public function test_admins_must_choose_a_strong_password_and_still_need_two_factor(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        $token = Password::createToken($admin);

        $this->post('/reset-password', ['token' => $token, 'email' => 'admin@example.com', 'password' => 'weakpass12', 'password_confirmation' => 'weakpass12'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $token, 'email' => 'admin@example.com', 'password' => 'StrongPass12', 'password_confirmation' => 'StrongPass12'])->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'StrongPass12'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }
}
