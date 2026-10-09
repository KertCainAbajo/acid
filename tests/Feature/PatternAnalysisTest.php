<?php

namespace Tests\Feature;

use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\User;
use App\Services\InsightService;
use App\Services\PatternAnalysisService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PatternAnalysisTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_food_that_is_usually_followed_by_symptoms_is_a_strong_trigger(): void
    {
        $this->travelTo('2026-10-09 23:00:00');
        $patient = User::factory()->create();
        foreach (['2026-10-03', '2026-10-05', '2026-10-07'] as $day) {
            $this->meal($patient, 'Coffee', 'acidic', "{$day} 08:00:00");
            $this->symptom($patient, 6, "{$day} 10:00:00");
            $this->meal($patient, 'Oatmeal', 'other', "{$day} 18:00:00");
        }

        $analysis = app(PatternAnalysisService::class)->analyze($patient);

        $coffee = collect($analysis['foods'])->firstWhere('name', 'Coffee');
        $oatmeal = collect($analysis['foods'])->firstWhere('name', 'Oatmeal');
        $this->assertSame(['eaten' => 3, 'followed' => 3, 'rate' => 1.0, 'confidence' => 'strong'], array_intersect_key($coffee, array_flip(['eaten', 'followed', 'rate', 'confidence'])));
        $this->assertSame('clear', $oatmeal['confidence']);
        $this->assertSame('Coffee', $analysis['foods'][0]['name']);
        $this->assertContains('Try a 2-week break from Coffee', array_column($analysis['recommendations'], 'title'));
        $this->assertStringContainsString('Oatmeal', collect($analysis['recommendations'])->firstWhere('key', 'safe-foods')['detail']);
    }

    public function test_symptoms_more_than_six_hours_after_a_meal_are_not_linked_to_it(): void
    {
        $this->travelTo('2026-10-09 23:00:00');
        $patient = User::factory()->create();
        $this->meal($patient, 'Pasta', 'other', '2026-10-08 08:00:00');
        $this->symptom($patient, 5, '2026-10-08 14:01:00');

        $analysis = app(PatternAnalysisService::class)->analyze($patient);

        $this->assertSame(0, $analysis['foods'][0]['followed']);
        $this->assertSame(0.0, $analysis['baselineRate']);
    }

    public function test_more_symptoms_than_the_previous_week_is_a_worsening_trend_that_raises_risk(): void
    {
        $this->travelTo('2026-10-09 23:00:00');
        $patient = User::factory()->create();
        $this->symptom($patient, 4, '2026-10-01 12:00:00');
        foreach (['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08'] as $day) {
            $this->symptom($patient, 7, "{$day} 12:00:00");
        }

        $analysis = app(PatternAnalysisService::class)->analyze($patient);

        $this->assertSame('worsening', $analysis['trend']['direction']);
        $this->assertSame(3, $analysis['trend']['symptomChange']);
        $this->assertSame(['frequency' => 20, 'severity' => 15, 'trend' => 5], array_column($analysis['risk']['factors'], 'points', 'key'));
        $this->assertSame(40, $analysis['risk']['score']);
        $this->assertSame('medium', $analysis['risk']['level']);
    }

    public function test_late_meals_are_detected_in_the_patients_own_timezone(): void
    {
        config(['app.patient_timezone' => 'Asia/Manila']);
        $this->travelTo('2026-10-09 15:00:00');
        $patient = User::factory()->create();
        foreach (['2026-10-04', '2026-10-05', '2026-10-06'] as $day) {
            $this->meal($patient, 'Late dinner', 'oily', "{$day} 13:00:00");
            $this->symptom($patient, 5, "{$day} 15:00:00");
            $this->meal($patient, 'Breakfast', 'other', "{$day} 00:30:00");
        }

        $analysis = app(PatternAnalysisService::class)->analyze($patient);

        $this->assertTrue($analysis['lateMeals']['isPattern']);
        $this->assertSame(1.0, $analysis['lateMeals']['lateRate']);
        $this->assertSame(0.0, $analysis['lateMeals']['earlierRate']);
        $this->assertSame('night', $analysis['timeOfDay']['peak']);
        $this->assertContains('late-meals', array_column($analysis['recommendations'], 'key'));
    }

    public function test_more_symptoms_after_short_nights_is_reported_as_a_sleep_link(): void
    {
        $this->travelTo('2026-10-09 23:00:00');
        $patient = User::factory()->create();
        foreach (['2026-10-02' => 5, '2026-10-03' => 5.5, '2026-10-04' => 8, '2026-10-05' => 7.5] as $day => $hours) {
            SleepLog::create(['user_id' => $patient->id, 'sleep_hours' => $hours, 'sleep_quality' => 3, 'stress_level' => 3, 'logged_on' => $day]);
        }
        $this->symptom($patient, 6, '2026-10-02 12:00:00');
        $this->symptom($patient, 6, '2026-10-03 12:00:00');

        $analysis = app(PatternAnalysisService::class)->analyze($patient);

        $this->assertSame(['shortNights' => 2, 'otherNights' => 2, 'shortAverage' => 1.0, 'otherAverage' => 0.0], $analysis['sleepLink']);
        $this->assertContains('sleep', array_column($analysis['recommendations'], 'key'));
    }

    public function test_generated_insight_stores_the_explainable_score(): void
    {
        $this->travelTo('2026-10-09 23:00:00');
        $patient = User::factory()->create();
        $this->symptom($patient, 8, '2026-10-09 12:00:00');

        $insight = app(InsightService::class)->generate($patient);

        $this->assertSame(20, $insight->risk_score);
        $this->assertSame('low', $insight->risk_level);
        $this->assertSame(['severity', 'frequency'], array_column($insight->fresh()->details['factors'], 'key'));
        $this->assertStringContainsString('Risk is low (20/100)', $insight->insight_text);
    }

    private function meal(User $patient, string $name, string $category, string $time): FoodLog
    {
        return FoodLog::create(['user_id' => $patient->id, 'food_name' => $name, 'category' => $category, 'time' => $time]);
    }

    private function symptom(User $patient, int $intensity, string $time): SymptomLog
    {
        return SymptomLog::create(['user_id' => $patient->id, 'symptom_type' => 'heartburn', 'intensity' => $intensity, 'time' => $time]);
    }
}
