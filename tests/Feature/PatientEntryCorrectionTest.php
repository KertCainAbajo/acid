<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientEntryCorrectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_patient_can_correct_their_own_food_entry(): void
    {
        $this->travelTo('2026-10-09 15:00:00');
        $patient = User::factory()->create();
        $meal = FoodLog::create(['user_id' => $patient->id, 'food_name' => 'Oatmeel', 'category' => 'other', 'time' => '2026-10-09 08:00:00']);
        Sanctum::actingAs($patient);

        $response = $this->putJson("/api/food-log/{$meal->id}", [
            'food_name' => 'Oatmeal with orange',
            'category' => 'acidic',
            'time' => '2026-10-09 07:30:00',
            'notes' => 'Fixed typo',
        ]);

        $response->assertOk()->assertJsonPath('data.food_name', 'Oatmeal with orange');
        $this->assertDatabaseHas('food_logs', ['id' => $meal->id, 'food_name' => 'Oatmeal with orange', 'category' => 'acidic', 'notes' => 'Fixed typo']);
        $this->assertDatabaseHas('system_logs', ['actor_id' => $patient->id, 'action' => 'food_log_updated', 'target_id' => $meal->id]);
        $this->assertSame(1, $patient->insights()->count());
    }

    public function test_patient_cannot_change_or_delete_another_patients_entries(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $meal = FoodLog::create(['user_id' => $owner->id, 'food_name' => 'Rice', 'category' => 'other', 'time' => now()->subHour()]);
        $symptom = SymptomLog::create(['user_id' => $owner->id, 'symptom_type' => 'pain', 'intensity' => 3, 'time' => now()->subHour()]);
        Sanctum::actingAs($intruder);

        $this->putJson("/api/food-log/{$meal->id}", ['food_name' => 'Hacked', 'category' => 'other', 'time' => now()->subHour()->toDateTimeString()])->assertForbidden();
        $this->deleteJson("/api/symptom-log/{$symptom->id}")->assertForbidden();

        $this->assertDatabaseHas('food_logs', ['id' => $meal->id, 'food_name' => 'Rice']);
        $this->assertModelExists($symptom);
    }

    public function test_patient_can_delete_their_own_symptom_entry(): void
    {
        $patient = User::factory()->create();
        $symptom = SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 6, 'time' => now()->subHour()]);
        Sanctum::actingAs($patient);

        $response = $this->deleteJson("/api/symptom-log/{$symptom->id}");

        $response->assertOk()->assertJsonPath('message', 'Symptom log deleted.');
        $this->assertModelMissing($symptom);
        $this->assertDatabaseHas('system_logs', ['actor_id' => $patient->id, 'action' => 'symptom_log_deleted', 'target_id' => $symptom->id]);
    }

    public function test_symptom_correction_is_validated(): void
    {
        $patient = User::factory()->create();
        $symptom = SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => 6, 'time' => now()->subHour()]);
        Sanctum::actingAs($patient);

        $response = $this->putJson("/api/symptom-log/{$symptom->id}", ['symptom_type' => 'heartburn', 'intensity' => 11, 'time' => now()->addDay()->toDateTimeString()]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['intensity', 'time']);
        $this->assertSame(6, $symptom->fresh()->intensity);
    }

    public function test_sleep_entry_cannot_be_moved_onto_a_day_that_already_has_one(): void
    {
        $this->travelTo('2026-10-09 09:00:00');
        $patient = User::factory()->create();
        $monday = SleepLog::create(['user_id' => $patient->id, 'sleep_hours' => 7, 'sleep_quality' => 3, 'stress_level' => 4, 'logged_on' => '2026-10-05']);
        SleepLog::create(['user_id' => $patient->id, 'sleep_hours' => 6, 'sleep_quality' => 2, 'stress_level' => 5, 'logged_on' => '2026-10-06']);
        Sanctum::actingAs($patient);

        $response = $this->putJson("/api/sleep-log/{$monday->id}", ['sleep_hours' => 8, 'sleep_quality' => 4, 'stress_level' => 2, 'logged_on' => '2026-10-06']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['logged_on']);
        $this->assertSame(7.0, $monday->fresh()->sleep_hours);
    }

    public function test_sleep_entry_can_be_corrected_on_its_own_day(): void
    {
        $this->travelTo('2026-10-09 09:00:00');
        $patient = User::factory()->create();
        $entry = SleepLog::create(['user_id' => $patient->id, 'sleep_hours' => 7, 'sleep_quality' => 3, 'stress_level' => 4, 'logged_on' => '2026-10-05']);
        Sanctum::actingAs($patient);

        $response = $this->putJson("/api/sleep-log/{$entry->id}", ['sleep_hours' => 4.5, 'sleep_quality' => 1, 'stress_level' => 9, 'logged_on' => '2026-10-05']);

        $response->assertOk();
        $this->assertSame(4.5, $entry->fresh()->sleep_hours);
    }
}
