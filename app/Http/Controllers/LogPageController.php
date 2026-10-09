<?php

namespace App\Http\Controllers;

use App\Models\FoodLog;
use App\Models\SymptomLog;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class LogPageController extends Controller
{
    /**
     * How many hours before a symptom a meal is shown as possible context, matching the trigger window used for insights.
     */
    private const MEAL_WINDOW_HOURS = 6;

    public function food(): View
    {
        $user = request()->user();
        $weekStart = CarbonImmutable::today()->subDays(6);
        $weekLogs = $user->foodLogs()->where('time', '>=', $weekStart)->get(['category', 'time']);
        $categoryBreakdown = collect(FoodLog::CATEGORIES)
            ->mapWithKeys(fn (string $category): array => [$category => $weekLogs->where('category', $category)->count()])
            ->all();

        return view('logs.food', [
            'title' => 'Food Intake Logger',
            'recentLogs' => $user->foodLogs()->latest('time')->limit(12)->get(),
            'todayCount' => $weekLogs->filter(fn (FoodLog $log): bool => $log->time->isToday())->count(),
            'weekCount' => $weekLogs->count(),
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }

    public function symptom(): View
    {
        $user = request()->user();
        $weekStart = CarbonImmutable::today()->subDays(6);
        $weekLogs = $user->symptomLogs()->where('time', '>=', $weekStart)->get(['symptom_type', 'intensity', 'time']);
        $recentLogs = $user->symptomLogs()->latest('time')->limit(12)->get();
        $recentMeals = $recentLogs->isEmpty() ? collect() : $user->foodLogs()
            ->whereBetween('time', [$recentLogs->min('time')->copy()->subHours(self::MEAL_WINDOW_HOURS), $recentLogs->max('time')])
            ->orderBy('time')
            ->get(['food_name', 'time']);

        return view('logs.symptom', [
            'title' => 'Symptom Tracker',
            'recentLogs' => $recentLogs,
            'mealsBeforeSymptom' => $recentLogs->mapWithKeys(fn (SymptomLog $symptom): array => [
                $symptom->id => $recentMeals
                    ->filter(fn (FoodLog $meal): bool => $meal->time->betweenIncluded($symptom->time->copy()->subHours(self::MEAL_WINDOW_HOURS), $symptom->time))
                    ->pluck('food_name')->unique()->values()->all(),
            ])->all(),
            'todayCount' => $weekLogs->filter(fn (SymptomLog $log): bool => $log->time->isToday())->count(),
            'weekCount' => $weekLogs->count(),
            'weekAverageIntensity' => $weekLogs->isEmpty() ? null : round((float) $weekLogs->avg('intensity'), 1),
            'typeBreakdown' => collect(SymptomLog::TYPES)
                ->mapWithKeys(fn (string $type): array => [$type => $weekLogs->where('symptom_type', $type)->count()])
                ->all(),
            'weeklySeverity' => collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weekLogs): array {
                $day = $weekStart->addDays($offset);
                $dayLogs = $weekLogs->filter(fn (SymptomLog $log): bool => $log->time->isSameDay($day));

                return [
                    'label' => $day->format('D'),
                    'date' => $day->format('M j'),
                    'isToday' => $day->isToday(),
                    'count' => $dayLogs->count(),
                    'maxIntensity' => (int) $dayLogs->max('intensity'),
                ];
            })->all(),
        ]);
    }

    public function sleep(): View
    {
        return $this->page('sleep', 'Sleep & Stress');
    }

    private function page(string $type, string $title): View
    {
        $user = request()->user();
        $recentLogs = match ($type) {
            'food' => $user->foodLogs()->latest('time')->limit(8)->get(),
            'symptom' => $user->symptomLogs()->latest('time')->limit(8)->get(),
            default => $user->sleepLogs()->latest('logged_on')->limit(8)->get(),
        };

        return view('logs.create', compact('type', 'title', 'recentLogs'));
    }
}
