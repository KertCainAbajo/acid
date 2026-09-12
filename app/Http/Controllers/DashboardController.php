<?php

namespace App\Http\Controllers;

use App\Services\InsightService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InsightService $insightService): View
    {
        $user = request()->user();
        $today = CarbonImmutable::today();
        $latestInsight = $user->insights()->latest('generated_at')->first() ?? $insightService->generate($user);
        $foods = $user->foodLogs()->latest('time')->limit(8)->get();
        $symptoms = $user->symptomLogs()->latest('time')->limit(8)->get();
        $sleep = $user->sleepLogs()->latest('logged_on')->first();
        $todayFoodCount = $user->foodLogs()->whereDate('time', $today)->count();
        $todaySymptomCount = $user->symptomLogs()->whereDate('time', $today)->count();
        $triggers = $insightService->findTriggers(
            $user->foodLogs()->where('time', '>=', now()->subDays(30))->get(),
            $user->symptomLogs()->where('time', '>=', now()->subDays(30))->get(),
        )->take(3);

        $timeline = collect()
            ->concat($foods->map(fn ($log) => ['kind' => 'food', 'at' => $log->time, 'title' => $log->food_name, 'meta' => ucfirst($log->category), 'tone' => 'safe']))
            ->concat($symptoms->map(fn ($log) => ['kind' => 'symptom', 'at' => $log->time, 'title' => ucfirst($log->symptom_type), 'meta' => "Intensity {$log->intensity}/10", 'tone' => 'alert']))
            ->sortByDesc('at')->take(6);

        return view('dashboard', compact(
            'user', 'latestInsight', 'foods', 'symptoms', 'sleep', 'todayFoodCount',
            'todaySymptomCount', 'triggers', 'timeline',
        ));
    }
}
