<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\SymptomLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PatientSectionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_patient_can_refresh_their_analysis(): void
    {
        $patient = User::factory()->create();
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 8, 'time' => now()->subHour()]);

        $response = $this->actingAs($patient)->post('/insights/refresh');

        $response->assertRedirect(route('insights'));
        $response->assertSessionHas('status', 'Your analysis was refreshed with your latest logs.');
        $this->assertSame(20, $patient->insights()->latest('id')->value('risk_score'));
    }

    public function test_insights_page_shows_the_food_trigger_analysis(): void
    {
        $patient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Spicy noodles', 'category' => 'spicy', 'time' => now()->subHours(5)]);
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 6, 'time' => now()->subHours(3)]);

        $response = $this->actingAs($patient)->get('/insights');

        $response->assertSee('Food trigger analysis');
        $response->assertSee('Spicy noodles');
        $response->assertSee('Why this score');
    }

    public function test_history_export_contains_only_the_patients_own_records(): void
    {
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Banana', 'category' => 'other', 'time' => '2026-10-01 08:15:00', 'notes' => 'Ripe']);
        SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'bloating', 'intensity' => 4, 'time' => '2026-10-01 10:00:00']);
        FoodLog::create(['user_id' => $otherPatient->id, 'food_name' => 'Someone else', 'category' => 'oily', 'time' => '2026-10-01 09:00:00']);

        $response = $this->actingAs($patient)->get('/health-history/export');

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Food,2026-10-01,08:15,Banana,Other,Ripe', $csv);
        $this->assertStringContainsString('Symptom,2026-10-01,10:00,Bloating,"Intensity 4/10",', $csv);
        $this->assertStringNotContainsString('Someone else', $csv);
    }
}
