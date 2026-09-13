<?php

namespace Tests\Feature\Api;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcidRefluxApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_and_log_health_data(): void
    {
        $registration = $this->postJson('/api/register', [
            'name' => 'Patient One',
            'email' => 'patient@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registration->assertCreated()->assertJsonPath('user.role', User::ROLE_PATIENT);
        $patient = User::where('email', 'patient@example.com')->firstOrFail();
        Sanctum::actingAs($patient);

        $this->postJson('/api/food-log', [
            'food_name' => 'Rice',
            'category' => 'oily',
            'time' => now()->subHour()->toDateTimeString(),
        ])->assertCreated()->assertJsonPath('data.food_name', 'Rice');

        $this->postJson('/api/symptom-log', [
            'symptom_type' => 'heartburn',
            'intensity' => 8,
            'time' => now()->toDateTimeString(),
        ])->assertCreated()->assertJsonPath('insight.risk_level', 'medium');

        $this->postJson('/api/sleep-log', [
            'sleep_hours' => 5.5,
            'sleep_quality' => 2,
            'stress_level' => 8,
            'logged_on' => today()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/history?from='.now()->subDay()->toDateString().'&to='.today()->toDateString())
            ->assertOk()
            ->assertJsonCount(3, 'timeline');

        $this->getJson('/api/recommendations')->assertOk()->assertJsonStructure([
            'safe_foods', 'avoid_foods', 'personalized_triggers',
        ]);
    }

    public function test_patient_cannot_read_another_patients_logs(): void
    {
        $owner = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create([
            'user_id' => $owner->id,
            'food_name' => 'Private food',
            'category' => 'acidic',
            'time' => now(),
        ]);
        Sanctum::actingAs($otherPatient);

        $this->getJson('/api/history')->assertOk()->assertJsonCount(0, 'food_logs');
    }

    public function test_admin_can_view_analytics_but_cannot_use_patient_routes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        FoodCategory::create(['name' => 'Test safe food', 'type' => 'safe']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/analytics')->assertOk()->assertJsonStructure([
            'total_users', 'risk_level_distribution', 'most_common_triggers',
        ]);
        $this->postJson('/api/admin/food-categories', [
            'name' => 'Admin managed food',
            'type' => 'safe',
        ])->assertCreated();
        $this->getJson('/api/admin/logs')->assertOk()->assertJsonPath('data.0.action', 'food_category_created');
        $this->postJson('/api/food-log', [
            'food_name' => 'Admin food',
            'category' => 'acidic',
            'time' => now()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_web_login_and_registration_pages_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Welcome back');
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_patient_can_use_the_web_dashboard(): void
    {
        $patient = User::factory()->create(['name' => 'Dashboard Patient']);

        $this->actingAs($patient)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('AI Pattern Insight');
    }

    public function test_authenticated_patient_can_open_each_patient_workspace_section(): void
    {
        $patient = User::factory()->create(['name' => 'Section Patient']);

        $this->actingAs($patient);

        $this->get('/food-log')->assertOk()->assertSee('Food Intake Logger');
        $this->get('/symptom-log')->assertOk()->assertSee('Symptom Tracker');
        $this->get('/sleep-stress')->assertOk()->assertSee('Sleep & Stress');
        $this->get('/insights')->assertOk()->assertSee('AI Pattern Insights');
        $this->get('/recommendations')->assertOk()->assertSee('Food Recommendations');
        $this->get('/health-history')->assertOk()->assertSee('Health History &amp; Profile', false);
    }
}
