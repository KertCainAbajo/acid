<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\Insight;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_summarises_patients_activity_risk_and_common_triggers(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice Patient']);
        $bob = User::factory()->create(['name' => 'Bob Patient', 'created_at' => '2026-08-01']);
        User::factory()->create(['name' => 'Inactive Patient', 'is_active' => false, 'created_at' => '2026-08-01']);
        foreach ([$alice, $bob] as $patient) {
            FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Coffee', 'category' => 'acidic', 'time' => '2026-10-09 08:00:00']);
            SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 6, 'time' => '2026-10-09 10:00:00']);
        }
        SleepLog::create(['user_id' => $alice->id, 'sleep_hours' => 7, 'sleep_quality' => 3, 'stress_level' => 3, 'logged_on' => '2026-09-30']);
        Insight::create(['user_id' => $alice->id, 'risk_level' => 'high', 'risk_score' => 70, 'insight_text' => 'x', 'generated_at' => now()]);
        Insight::create(['user_id' => $bob->id, 'risk_level' => 'low', 'risk_score' => 10, 'insight_text' => 'x', 'generated_at' => now()->subDay()]);
        Insight::create(['user_id' => $bob->id, 'risk_level' => 'medium', 'risk_score' => 30, 'insight_text' => 'x', 'generated_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('patientCount', 3);
        $response->assertViewHas('activePatientCount', 2);
        $response->assertViewHas('activeThisWeek', 2);
        $response->assertViewHas('newPatientsThisWeek', 1);
        $response->assertViewHas('entriesThisWeek', 4);
        $response->assertViewHas('entriesLastWeek', 1);
        $response->assertViewHas('riskDistribution', ['low' => 0, 'medium' => 1, 'high' => 1, 'none' => 1]);
        $response->assertViewHas('commonTriggers', fn ($triggers): bool => $triggers->first() === ['food' => 'Coffee', 'associations' => 2, 'patients' => 2]);
        $response->assertSee('Alice Patient');
    }

    public function test_patients_cannot_open_the_admin_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin/dashboard');

        $response->assertForbidden();
    }
}
