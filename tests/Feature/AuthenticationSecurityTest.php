<?php

namespace Tests\Feature;

use App\Models\SleepLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_web_login_is_locked_after_five_failed_attempts(): void
    {
        $patient = User::factory()->create();
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $patient->email, 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['email' => $patient->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Too many attempts. Please try again in 60 seconds.']);
        $this->assertGuest();
    }

    public function test_api_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $patient = User::factory()->create();
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => $patient->email, 'password' => 'wrong-password'])->assertUnprocessable();
        }

        $response = $this->postJson('/api/login', ['email' => $patient->email, 'password' => 'password']);

        $response->assertTooManyRequests();
    }

    public function test_web_logout_revokes_the_pages_api_token(): void
    {
        $patient = User::factory()->create();
        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $this->get('/dashboard')->assertOk();
        $tokenId = session('api_token_id');
        $this->assertNotNull(PersonalAccessToken::findOrFail($tokenId)->expires_at);

        $this->post('/logout');

        $this->assertNull(PersonalAccessToken::find($tokenId));
        $this->assertGuest();
    }

    public function test_deactivated_patient_is_signed_out_of_the_web_app(): void
    {
        $patient = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($patient)->get('/food-log');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'This account has been deactivated.']);
        $this->assertGuest();
    }

    public function test_deactivating_a_patient_revokes_their_api_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $patient = User::factory()->create();
        $patient->createToken('mobile-app');
        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/admin/users/{$patient->id}", ['is_active' => false]);

        $response->assertOk();
        $this->assertSame(0, $patient->tokens()->count());
    }

    public function test_saving_sleep_twice_for_the_same_day_updates_that_day(): void
    {
        $this->travelTo('2026-10-09 09:00:00');
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);
        $entry = ['sleep_hours' => 7, 'sleep_quality' => 3, 'stress_level' => 4, 'logged_on' => '2026-10-09'];
        $this->postJson('/api/sleep-log', $entry)->assertCreated();

        $response = $this->postJson('/api/sleep-log', [...$entry, 'sleep_hours' => 5.5]);

        $response->assertOk()->assertJsonPath('message', 'Sleep and stress log updated for that day.');
        $this->assertSame(1, SleepLog::where('user_id', $patient->id)->count());
        $this->assertSame(5.5, SleepLog::where('user_id', $patient->id)->value('sleep_hours'));
    }

    public function test_admins_cannot_open_patient_only_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/food-log');

        $response->assertForbidden();
    }

    public function test_google_sign_in_always_starts_on_the_configured_app_address(): void
    {
        config(['app.url' => 'http://localhost:8000', 'services.google.client_id' => 'test-client']);

        $response = $this->get('http://127.0.0.1:8000/auth/google/redirect');

        $response->assertRedirect('http://localhost:8000/auth/google/redirect');
    }

    public function test_cancelled_google_sign_in_explains_what_happened(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Google sign-in was cancelled.']);
    }

    public function test_expired_google_sign_in_session_explains_how_to_retry(): void
    {
        config(['app.url' => 'http://localhost:8000']);
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $response = $this->get('/auth/google/callback?code=abc&state=stale');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your Google sign-in session expired. Please try again, and open the app at http://localhost:8000 so the sign-in can finish on the same address.']);
        $this->assertGuest();
    }

    public function test_google_sign_in_rejects_an_unverified_email(): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => false])
            ->map(['id' => 'google-123', 'name' => 'Unverified Person', 'email' => 'unverified@example.com']);
        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your Google account must have a verified email address.']);
        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
        $this->assertGuest();
    }
}
