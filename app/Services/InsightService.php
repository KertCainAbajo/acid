<?php

namespace App\Services;

use App\Models\FoodLog;
use App\Models\Insight;
use App\Models\SymptomLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InsightService
{
    public function generate(User $user, int $lookbackDays = 7): Insight
    {
        $end = CarbonImmutable::now();
        $start = $end->subDays($lookbackDays);
        $foods = $this->foods($user, $start, $end);
        $symptoms = $this->symptoms($user, $start, $end);
        $sleep = $this->sleep($user, $start, $end);
        $triggers = $this->findTriggers($foods, $symptoms);

        $symptomCount = $symptoms->count();
        $averageIntensity = $symptomCount > 0 ? round((float) $symptoms->avg('intensity'), 1) : 0;
        $averageSleep = $sleep->count() > 0 ? round((float) $sleep->avg('sleep_hours'), 1) : null;
        $averageStress = $sleep->count() > 0 ? round((float) $sleep->avg('stress_level'), 1) : null;

        $symptomScore = min(35, ($symptomCount * 5) + ($averageIntensity * 2));
        $triggerScore = min(30, $triggers->count() * 5);
        $sleepScore = 0;
        if ($averageSleep !== null && $averageSleep < 6) {
            $sleepScore += 15;
        } elseif ($averageSleep !== null && $averageSleep < 7) {
            $sleepScore += 7;
        }
        if ($averageStress !== null && $averageStress >= 8) {
            $sleepScore += 10;
        } elseif ($averageStress !== null && $averageStress >= 6) {
            $sleepScore += 5;
        }

        $score = $symptomScore + $triggerScore + $sleepScore;
        $riskLevel = $score >= 60 ? 'high' : ($score >= 25 ? 'medium' : 'low');
        $lines = [
            sprintf('Weekly summary: %d symptom%s recorded from %s to %s, with an average intensity of %s/10.', $symptomCount, $symptomCount === 1 ? '' : 's', $start->toDateString(), $end->toDateString(), $averageIntensity),
            sprintf('You logged %d food entr%s and %d sleep entr%s.', $foods->count(), $foods->count() === 1 ? 'y' : 'ies', $sleep->count(), $sleep->count() === 1 ? 'y' : 'ies'),
        ];

        if ($triggers->isNotEmpty()) {
            $lines[] = 'Potential food triggers: '.$triggers->map(fn (array $trigger) => sprintf('%s (%d association%s)', $trigger['food'], $trigger['count'], $trigger['count'] === 1 ? '' : 's'))->implode(', ').'.';
        } else {
            $lines[] = 'No consistent food trigger was identified in this period.';
        }

        if ($averageSleep !== null) {
            $lines[] = sprintf('Sleep averaged %s hours and stress averaged %s/10.', $averageSleep, $averageStress);
        } else {
            $lines[] = 'Add sleep and stress entries to improve the reliability of this risk estimate.';
        }

        $lines[] = 'Risk level is '.$riskLevel.' based on symptom frequency, food associations, and sleep/stress patterns. This is a tracking aid, not a medical diagnosis.';

        return $user->insights()->create([
            'risk_level' => $riskLevel,
            'insight_text' => implode(' ', $lines),
            'generated_at' => now(),
        ]);
    }

    public function findTriggers(Collection $foods, Collection $symptoms): Collection
    {
        return $foods->map(function (FoodLog $food) use ($symptoms): ?array {
            $relatedSymptoms = $symptoms->filter(fn (SymptomLog $symptom) => $symptom->time->greaterThanOrEqualTo($food->time)
                && $symptom->time->lessThanOrEqualTo($food->time->copy()->addHours(6))
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

    private function foods(User $user, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $user->foodLogs()->whereBetween('time', [$start, $end])->get();
    }

    private function symptoms(User $user, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $user->symptomLogs()->whereBetween('time', [$start, $end])->get();
    }

    private function sleep(User $user, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $user->sleepLogs()->whereBetween('logged_on', [$start->toDateString(), $end->toDateString()])->get();
    }
}
