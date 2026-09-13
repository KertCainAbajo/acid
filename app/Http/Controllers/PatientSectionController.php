<?php

namespace App\Http\Controllers;

use App\Models\FoodCategory;
use App\Services\InsightService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class PatientSectionController extends Controller
{
    public function insights(InsightService $insightService): View
    {
        $user = request()->user();
        $latestInsight = $user->insights()->latest('generated_at')->first() ?? $insightService->generate($user);
        $insightHistory = $user->insights()->latest('generated_at')->limit(8)->get();
        $from = now()->subDays(7);

        return view('sections.patient', [
            'section' => 'insights',
            'title' => 'AI Pattern Insights',
            'description' => 'A clearer view of how your meals, symptoms, sleep, and stress may connect over time.',
            'user' => $user,
            'latestInsight' => $latestInsight,
            'insightHistory' => $insightHistory,
            'foodCount' => $user->foodLogs()->where('time', '>=', $from)->count(),
            'symptomCount' => $user->symptomLogs()->where('time', '>=', $from)->count(),
            'sleepCount' => $user->sleepLogs()->where('logged_on', '>=', $from->toDateString())->count(),
        ]);
    }

    public function recommendations(InsightService $insightService): View
    {
        $user = request()->user();
        $foods = $user->foodLogs()->where('time', '>=', now()->subDays(30))->get();
        $symptoms = $user->symptomLogs()->where('time', '>=', now()->subDays(30))->get();

        return view('sections.patient', [
            'section' => 'recommendations',
            'title' => 'Food Recommendations',
            'description' => 'Use your own logged patterns and general food group guidance to plan calmer meals.',
            'user' => $user,
            'safeFoods' => FoodCategory::where('type', 'safe')->orderBy('name')->get(),
            'avoidFoods' => FoodCategory::where('type', 'avoid')->orderBy('name')->get(),
            'triggers' => $insightService->findTriggers($foods, $symptoms)->take(6),
        ]);
    }

    public function history(): View
    {
        $user = request()->user();
        $foods = $user->foodLogs()->latest('time')->limit(12)->get();
        $symptoms = $user->symptomLogs()->latest('time')->limit(12)->get();
        $sleepLogs = $user->sleepLogs()->latest('logged_on')->limit(8)->get();
        $timeline = collect()
            ->concat($foods->map(fn ($log) => [
                'kind' => 'Food',
                'title' => $log->food_name,
                'detail' => ucfirst($log->category),
                'at' => $log->time,
                'tone' => 'safe',
            ]))
            ->concat($symptoms->map(fn ($log) => [
                'kind' => 'Symptom',
                'title' => ucfirst($log->symptom_type),
                'detail' => "Intensity {$log->intensity}/10",
                'at' => $log->time,
                'tone' => 'alert',
            ]))
            ->concat($sleepLogs->map(fn ($log) => [
                'kind' => 'Sleep',
                'title' => number_format($log->sleep_hours, 1).' hours',
                'detail' => "Quality {$log->sleep_quality}/5 · Stress {$log->stress_level}/10",
                'at' => CarbonImmutable::parse($log->logged_on),
                'tone' => 'calm',
            ]))
            ->sortByDesc('at')
            ->values();

        return view('sections.patient', [
            'section' => 'history',
            'title' => 'Health History & Profile',
            'description' => 'Review your personal details and the timeline of records you have chosen to track.',
            'user' => $user,
            'timeline' => $timeline,
            'foodCount' => $user->foodLogs()->count(),
            'symptomCount' => $user->symptomLogs()->count(),
            'sleepCount' => $user->sleepLogs()->count(),
        ]);
    }
}
