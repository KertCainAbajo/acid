<?php

namespace Tests\Feature\Api;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SymptomLog;
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

    public function test_dashboard_weekly_activity_counts_only_the_patients_last_seven_days(): void
    {
        $this->travelTo('2026-10-09 15:00:00');
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Coffee', 'category' => 'acidic', 'time' => '2026-10-09 08:00:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Pizza', 'category' => 'oily', 'time' => '2026-10-09 12:00:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Old meal', 'category' => 'oily', 'time' => '2026-10-02 12:00:00']);
        FoodLog::create(['user_id' => $otherPatient->id, 'food_name' => 'Not mine', 'category' => 'oily', 'time' => '2026-10-09 09:00:00']);
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 4, 'time' => '2026-10-03 21:00:00']);
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'pain', 'intensity' => 8, 'time' => '2026-10-03 23:00:00']);

        $response = $this->actingAs($patient)->get('/dashboard');

        $response->assertViewHas('weeklyAverageIntensity', 6.0);
        $response->assertViewHas('weeklyActivity', function (array $days): bool {
            return count($days) === 7
                && $days[0]['date'] === 'Oct 3' && $days[0]['foods'] === 0 && $days[0]['symptoms'] === 2 && $days[0]['maxIntensity'] === 8
                && $days[6]['date'] === 'Oct 9' && $days[6]['isToday'] && $days[6]['foods'] === 2 && $days[6]['symptoms'] === 0;
        });
    }

    public function test_food_intake_page_summarises_only_the_patients_last_seven_days(): void
    {
        $this->travelTo('2026-10-09 15:00:00');
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Orange juice', 'category' => 'acidic', 'time' => '2026-10-09 08:00:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Lemon tea', 'category' => 'acidic', 'time' => '2026-10-05 10:00:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Fries', 'category' => 'oily', 'time' => '2026-10-03 12:00:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Old curry', 'category' => 'spicy', 'time' => '2026-10-02 12:00:00']);
        FoodLog::create(['user_id' => $otherPatient->id, 'food_name' => 'Not mine', 'category' => 'dairy', 'time' => '2026-10-09 09:00:00']);

        $response = $this->actingAs($patient)->get('/food-log');

        $response->assertViewHas('todayCount', 1);
        $response->assertViewHas('weekCount', 3);
        $response->assertViewHas('categoryBreakdown', ['acidic' => 2, 'oily' => 1, 'spicy' => 0, 'dairy' => 0, 'other' => 0]);
        $response->assertSee('Orange juice');
        $response->assertDontSee('Not mine');
    }

    public function test_symptom_tracker_summarises_the_week_and_links_meals_from_the_six_hours_before(): void
    {
        $this->travelTo('2026-10-09 15:00:00');
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Coffee', 'category' => 'acidic', 'time' => '2026-10-09 08:30:00']);
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Breakfast burrito', 'category' => 'spicy', 'time' => '2026-10-09 05:59:00']);
        FoodLog::create(['user_id' => $otherPatient->id, 'food_name' => 'Not my pizza', 'category' => 'oily', 'time' => '2026-10-09 11:00:00']);
        $todaySymptom = SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 7, 'time' => '2026-10-09 12:00:00']);
        $earlierSymptom = SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'bloating', 'intensity' => 4, 'time' => '2026-10-05 20:00:00']);
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'pain', 'intensity' => 9, 'time' => '2026-10-02 20:00:00']);

        $response = $this->actingAs($patient)->get('/symptom-log');

        $response->assertViewHas('todayCount', 1);
        $response->assertViewHas('weekCount', 2);
        $response->assertViewHas('weekAverageIntensity', 5.5);
        $response->assertViewHas('typeBreakdown', ['heartburn' => 1, 'bloating' => 1, 'pain' => 0]);
        $response->assertViewHas('mealsBeforeSymptom', fn (array $meals): bool => $meals[$todaySymptom->id] === ['Coffee'] && $meals[$earlierSymptom->id] === []);
        $response->assertSee('Symptom Tracker');
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

    public function test_patient_search_only_returns_their_own_matching_logs(): void
    {
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create([
            'user_id' => $patient->id,
            'food_name' => 'Banana oatmeal',
            'category' => 'other',
            'time' => now(),
        ]);
        FoodLog::create([
            'user_id' => $otherPatient->id,
            'food_name' => 'Private banana meal',
            'category' => 'other',
            'time' => now(),
        ]);

        $this->actingAs($patient)->get('/search-logs?q=banana')
            ->assertOk()
            ->assertSee('Banana oatmeal')
            ->assertDontSee('Private banana meal');
    }
}
