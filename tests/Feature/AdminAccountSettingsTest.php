<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@example.com', 'password' => 'OldPassword1']);
    }

    public function test_admin_can_open_account_settings(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/settings');

        $response->assertOk()->assertSee('Account settings')->assertSee('admin@example.com');
    }

    public function test_patients_cannot_open_admin_account_settings(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin/settings');

        $response->assertForbidden();
    }

    public function test_admin_can_change_their_name_and_email_after_confirming_their_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put('/admin/settings/profile', ['name' => 'Kert Admin', 'email' => 'kert@refluxcare.test', 'current_password' => 'OldPassword1']);

        $response->assertRedirect(route('admin.settings'))->assertSessionHas('status', 'Profile saved. Use your new email address the next time you sign in.');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Kert Admin', 'email' => 'kert@refluxcare.test']);
        $this->assertDatabaseHas('system_logs', ['actor_id' => $admin->id, 'action' => 'account_profile_updated']);
    }

    public function test_profile_change_is_rejected_with_a_wrong_password_or_a_taken_email(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->put('/admin/settings/profile', ['name' => 'X', 'email' => 'new@example.com', 'current_password' => 'wrong'])
            ->assertSessionHasErrorsIn('profile', ['current_password' => 'Your current password is incorrect.']);
        $this->actingAs($admin)->put('/admin/settings/profile', ['name' => 'X', 'email' => 'taken@example.com', 'current_password' => 'OldPassword1'])
            ->assertSessionHasErrorsIn('profile', ['email' => 'Another account already uses this email address.']);

        $this->assertSame('admin@example.com', $admin->fresh()->email);
    }

    public function test_admin_can_change_their_password_and_other_tokens_are_revoked(): void
    {
        $admin = $this->admin();
        $admin->createToken('other-device');

        $response = $this->actingAs($admin)->put('/admin/settings/password', [
            'current_password' => 'OldPassword1',
            'password' => 'BrandNewPass9',
            'password_confirmation' => 'BrandNewPass9',
        ]);

        $response->assertRedirect(route('admin.settings'))->assertSessionHas('status', 'Password changed. Other devices have been signed out.');
        $this->assertTrue(Hash::check('BrandNewPass9', $admin->fresh()->password));
        $this->assertSame(0, $admin->tokens()->count());
        $this->assertDatabaseHas('system_logs', ['actor_id' => $admin->id, 'action' => 'account_password_changed']);
    }

    public function test_weak_or_unchanged_passwords_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/settings/password', ['current_password' => 'OldPassword1', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrorsIn('password', ['password']);
        $this->actingAs($admin)->put('/admin/settings/password', ['current_password' => 'OldPassword1', 'password' => 'OldPassword1', 'password_confirmation' => 'OldPassword1'])
            ->assertSessionHasErrorsIn('password', ['password' => 'Choose a new password that is different from your current one.']);

        $this->assertTrue(Hash::check('OldPassword1', $admin->fresh()->password));
    }

    public function test_admin_seeder_does_not_overwrite_an_existing_admin(): void
    {
        $admin = $this->admin();
        $admin->update(['email' => 'changed@example.com']);

        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
        $this->assertTrue(Hash::check('OldPassword1', $admin->fresh()->password));
    }

    public function test_reset_command_recovers_a_locked_out_admin(): void
    {
        $admin = $this->admin();

        $this->artisan('admin:reset-password', ['--password' => 'Recovered123'])
            ->expectsOutput('Password reset for admin@example.com.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('Recovered123', $admin->fresh()->password));
    }
}
