<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_cannot_grant_the_admin_role(): void
    {
        $this->post('/register', ['name' => 'Sneaky', 'email' => 'web@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin']);
        $this->postJson('/api/register', ['name' => 'Sneaky', 'email' => 'api@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin'])->assertCreated();

        $this->assertSame(['patient', 'patient'], User::whereIn('email', ['web@example.com', 'api@example.com'])->pluck('role')->all());
    }

    public function test_api_patient_deletion_requires_the_admins_password(): void
    {
        $patient = User::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson("/api/admin/users/{$patient->id}")->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->deleteJson("/api/admin/users/{$patient->id}", ['current_password' => 'wrong'])->assertUnprocessable();
        $this->assertModelExists($patient);

        $this->deleteJson("/api/admin/users/{$patient->id}", ['current_password' => 'password'])->assertOk();
        $this->assertModelMissing($patient);
    }

    public function test_api_cannot_change_or_delete_another_administrator(): void
    {
        $otherAdmin = User::factory()->admin()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson("/api/admin/users/{$otherAdmin->id}", ['is_active' => false])->assertForbidden();
        $this->deleteJson("/api/admin/users/{$otherAdmin->id}", ['current_password' => 'password'])->assertForbidden();

        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_api_role_change_requires_the_admins_password(): void
    {
        $patient = User::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson("/api/admin/users/{$patient->id}", ['role' => 'admin'])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertSame(User::ROLE_PATIENT, $patient->fresh()->role);
    }

    public function test_csv_exports_neutralise_spreadsheet_formulas(): void
    {
        $patient = User::factory()->create(['name' => '=HYPERLINK("http://evil.test","Click")']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => '+cmd|calc', 'category' => 'acidic', 'time' => now()->subHour()]);

        $adminExport = $this->actingAs(User::factory()->admin()->create())->get('/admin/patients/export')->streamedContent();
        $patientExport = $this->actingAs($patient)->get('/health-history/export')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $adminExport);
        $this->assertStringNotContainsString(',"=HYPERLINK', $adminExport);
        $this->assertStringContainsString("'+cmd|calc", $patientExport);
    }

    public function test_logged_food_names_are_escaped_when_shown(): void
    {
        $patient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => '<script>alert(1)</script>', 'category' => 'acidic', 'time' => now()->subHour()]);

        $response = $this->actingAs($patient)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
