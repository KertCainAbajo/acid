<?php

namespace App\Services;

use App\Models\FoodLog;
use App\Models\Insight;
use App\Models\SymptomLog;
use App\Models\User;
use Illuminate\Support\Collection;

class InsightService
{
    public function __construct(private readonly PatternAnalysisService $patternAnalysis) {}

    /**
     * Analyse the patient's recent logs and store the result as their latest insight.
     */
    public function generate(User $user): Insight
    {
        $analysis = $this->patternAnalysis->analyze($user);

        return $user->insights()->create([
            'risk_level' => $analysis['risk']['level'],
            'risk_score' => $analysis['risk']['score'],
            'insight_text' => $analysis['summary'],
            'details' => [
                'headline' => $analysis['headline'],
                'factors' => $analysis['risk']['factors'],
                'trend' => $analysis['trend']['direction'],
                'symptoms' => $analysis['week']['symptoms'],
                'confidence' => $analysis['dataQuality']['level'],
            ],
            'generated_at' => $analysis['generatedAt'],
        ]);
    }

    /**
     * Foods followed by at least one symptom within the trigger window, most associations first.
     *
     * @param  Collection<int, FoodLog>  $foods
     * @param  Collection<int, SymptomLog>  $symptoms
     * @return Collection<int, array{food: string, count: int}>
     */
    public function findTriggers(Collection $foods, Collection $symptoms): Collection
    {
        return $foods->map(function (FoodLog $food) use ($symptoms): ?array {
            $relatedSymptoms = $symptoms->filter(fn (SymptomLog $symptom) => $symptom->time->greaterThanOrEqualTo($food->time)
                && $symptom->time->lessThanOrEqualTo($food->time->copy()->addHours(PatternAnalysisService::MEAL_WINDOW_HOURS))
            );

            return $relatedSymptoms->isNotEmpty() ? [
                'food' => $food->food_name,
                'count' => $relatedSymptoms->count(),
            ] : null;
        })->filter()->groupBy('food')->map(fn (Collection $matches, string $food) => [
            'food' => $food,
            'count' => $matches->sum('count'),
        ])->sortByDesc('count')->values();
    }
}
