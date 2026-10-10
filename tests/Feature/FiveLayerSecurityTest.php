<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class FiveLayerSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXP';

    private function currentCode(): string
    {
        return app(TwoFactorService::class)->codeAt(self::SECRET, intdiv(now()->getTimestamp(), 30));
    }

    private function failLogins(User $user, int $times): void
    {
        foreach (range(1, $times) as $attempt) {
            if ($attempt > 1 && ($attempt - 1) % 5 === 0) {
                $this->travel(61)->seconds();
            }
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }
    }

    // Layer 1: strong sign-in

    public function test_account_is_locked_after_ten_failed_sign_ins_even_with_the_right_password(): void
    {
        $patient = User::factory()->create();

        $this->failLogins($patient, 10);
        $this->travel(61)->seconds();
        $response = $this->post('/login', ['email' => $patient->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'This account is temporarily locked after too many failed sign-in attempts. Try again in 14 minutes.']);
        $this->assertGuest();
        $this->assertTrue($patient->fresh()->isLocked());
        $this->assertDatabaseHas('system_logs', ['action' => 'account_locked', 'target_id' => $patient->id]);
        $this->assertSame(10, SystemLog::where('action', 'login_failed')->count());
    }

    public function test_lock_expires_and_admin_can_unlock_early(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create(['locked_until' => now()->addMinutes(15)]);

        $this->actingAs($admin)->post("/admin/security/unlock/{$patient->id}")->assertSessionHas('status');

        $this->assertFalse($patient->fresh()->isLocked());
        $this->assertDatabaseHas('system_logs', ['actor_id' => $admin->id, 'action' => 'account_unlocked', 'target_id' => $patient->id]);
    }

    public function test_registration_requires_a_password_with_letters_and_numbers(): void
    {
        $response = $this->post('/register', ['name' => 'Weak', 'email' => 'weak@example.com', 'password' => 'onlyletters', 'password_confirmation' => 'onlyletters']);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    // Layer 2: two-factor authentication

    public function test_totp_codes_match_the_rfc_6238_reference_values(): void
    {
        $twoFactor = app(TwoFactorService::class);

        $this->assertSame('287082', $twoFactor->codeAt('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1));
        $this->assertSame('081804', $twoFactor->codeAt('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 37037036));
    }

    public function test_two_factor_account_needs_a_valid_code_before_it_is_signed_in(): void
    {
        $patient = User::factory()->withTwoFactor(self::SECRET)->create();

        $this->post('/login', ['email' => $patient->email, 'password' => 'password'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => $this->currentCode()])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($patient);
    }

    public function test_a_two_factor_code_cannot_be_reused(): void
    {
        $this->travelTo('2026-10-10 12:00:05');
        $patient = User::factory()->withTwoFactor(self::SECRET)->create();
        $code = $this->currentCode();
        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $code]);
        $this->post('/logout');

        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_signs_in_once(): void
    {
        $patient = User::factory()->withTwoFactor(self::SECRET)->create();

        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-11111']);

        $this->assertAuthenticatedAs($patient);
        $this->assertSame(['BBBBB-22222'], $patient->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('system_logs', ['action' => 'recovery_code_used', 'target_id' => $patient->id]);
    }

    public function test_admin_without_two_factor_must_set_it_up_before_using_the_console(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertRedirect(route('admin.settings'));
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Set up two-factor');

        $this->actingAs($admin)->post('/security/two-factor');
        $secret = $admin->fresh()->two_factor_secret;
        $code = app(TwoFactorService::class)->codeAt($secret, intdiv(now()->getTimestamp(), 30));
        $this->actingAs($admin)->post('/security/two-factor/confirm', ['code' => $code])->assertSessionHas('recovery_codes');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_admins_cannot_turn_two_factor_off_but_patients_can_with_their_password(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->withTwoFactor()->create();

        $this->actingAs($admin)->delete('/security/two-factor', ['current_password' => 'password']);
        $this->actingAs($patient)->delete('/security/two-factor', ['current_password' => 'wrong'])->assertSessionHasErrorsIn('twoFactor', ['current_password']);
        $this->actingAs($patient)->delete('/security/two-factor', ['current_password' => 'password']);

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->assertFalse($patient->fresh()->hasTwoFactorEnabled());
    }

    public function test_api_login_requires_the_two_factor_code_and_issues_an_expiring_token(): void
    {
        $patient = User::factory()->withTwoFactor(self::SECRET)->create();

        $this->postJson('/api/login', ['email' => $patient->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonPath('two_factor_required', true);
        $response = $this->postJson('/api/login', ['email' => $patient->email, 'password' => 'password', 'two_factor_code' => $this->currentCode()]);

        $response->assertOk();
        $this->assertNotNull(PersonalAccessToken::findToken($response->json('token'))->expires_at);
    }

    // Layer 3: session protection

    public function test_idle_sessions_are_signed_out_sooner_for_admins_than_patients(): void
    {
        $patient = User::factory()->create();
        $this->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $this->travel(59)->minutes();
        $this->get('/dashboard')->assertOk();

        $this->travel(61)->minutes();
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'You were signed out after 60 minutes of inactivity. Please sign in again.']);
        $this->assertGuest();
        $this->assertDatabaseHas('system_logs', ['action' => 'session_timed_out', 'target_id' => $patient->id]);
        $this->assertSame(15, User::factory()->admin()->make()->idleTimeoutMinutes());
    }

    public function test_a_session_used_from_a_different_browser_is_blocked(): void
    {
        $patient = User::factory()->create();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/150')->post('/login', ['email' => $patient->email, 'password' => 'password']);

        $response = $this->withHeader('User-Agent', 'curl/8.0 stolen-cookie')->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('system_logs', ['action' => 'session_blocked', 'target_id' => $patient->id]);
    }

    public function test_signing_out_other_sessions_requires_the_password_and_revokes_other_tokens(): void
    {
        $patient = User::factory()->create();
        $patient->createToken('old-phone');

        $this->actingAs($patient)->post('/security/sessions/sign-out-others', ['current_password' => 'nope'])->assertSessionHasErrorsIn('sessions', ['current_password']);
        $this->assertSame(1, $patient->tokens()->where('name', 'old-phone')->count());
        $this->actingAs($patient)->post('/security/sessions/sign-out-others', ['current_password' => 'password'])->assertSessionHas('status');

        $this->assertSame(0, $patient->tokens()->where('name', 'old-phone')->count());
    }

    // Layer 4: HTTP hardening

    public function test_every_response_carries_security_headers_and_private_pages_are_not_cached(): void
    {
        $public = $this->get('/login');
        $public->assertHeader('X-Frame-Options', 'DENY');
        $public->assertHeader('X-Content-Type-Options', 'nosniff');
        $public->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $public->headers->get('Content-Security-Policy'));

        $private = $this->actingAs(User::factory()->create())->get('/dashboard');

        $this->assertStringContainsString('no-store', $private->headers->get('Cache-Control'));
    }

    // Layer 5: monitoring and audit

    public function test_security_center_shows_failed_sign_ins_locked_accounts_and_suspicious_addresses(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'target@example.com']);
        $this->failLogins($target, 10);

        $response = $this->actingAs($admin)->get('/admin/security');

        $response->assertOk();
        $response->assertViewHas('failedToday', 10);
        $response->assertViewHas('lockedAccounts', fn ($accounts): bool => $accounts->pluck('email')->all() === ['target@example.com']);
        $response->assertViewHas('suspiciousIps', fn ($ips): bool => $ips->first()['failures'] === 10);
        $response->assertSee('Elevated');
    }

    public function test_sign_in_from_a_new_browser_is_flagged(): void
    {
        $patient = User::factory()->create();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/150')->post('/login', ['email' => $patient->email, 'password' => 'password']);
        $this->post('/logout');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Safari/605')->post('/login', ['email' => $patient->email, 'password' => 'password']);

        $this->assertDatabaseHas('system_logs', ['action' => 'new_device_sign_in', 'target_id' => $patient->id]);
        $this->assertSame(1, SystemLog::where('action', 'new_device_sign_in')->count());
    }

    public function test_deleting_a_patient_requires_the_admins_password(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create(['email' => 'keep-me@example.com']);

        $this->actingAs($admin)->delete("/admin/patients/{$patient->id}", ['confirm_email' => 'keep-me@example.com', 'current_password' => 'wrong'])
            ->assertSessionHasErrorsIn('delete', ['current_password']);

        $this->assertModelExists($patient);
    }

    public function test_patient_can_open_their_account_security_page(): void
    {
        $patient = User::factory()->create();

        $response = $this->actingAs($patient)->get('/account/security');

        $response->assertOk()->assertSee('Account security')->assertSee('Two-factor authentication')->assertSee('Sign-in history');
    }
}
