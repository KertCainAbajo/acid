<?php

namespace App\Http\Controllers;

use App\Models\FoodLog;
use App\Models\SymptomLog;
use App\Services\InsightService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InsightService $insightService): RedirectResponse|View
    {
        $user = request()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

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

        $weekStart = $today->subDays(6);
        $weekFoods = $user->foodLogs()->where('time', '>=', $weekStart)->get(['time']);
        $weekSymptoms = $user->symptomLogs()->where('time', '>=', $weekStart)->get(['time', 'intensity']);
        $weeklyActivity = $this->weeklyActivity($weekStart, $weekFoods, $weekSymptoms);
        $weeklyAverageIntensity = $weekSymptoms->isEmpty() ? 0.0 : round((float) $weekSymptoms->avg('intensity'), 1);

        $timeline = collect()
            ->concat($foods->map(fn ($log) => ['kind' => 'food', 'at' => $log->time, 'title' => $log->food_name, 'meta' => ucfirst($log->category), 'tone' => 'safe']))
            ->concat($symptoms->map(fn ($log) => ['kind' => 'symptom', 'at' => $log->time, 'title' => ucfirst($log->symptom_type), 'meta' => "Intensity {$log->intensity}/10", 'tone' => 'alert']))
            ->sortByDesc('at')->take(6);

        return view('dashboard', compact(
            'user', 'latestInsight', 'foods', 'symptoms', 'sleep', 'todayFoodCount',
            'todaySymptomCount', 'triggers', 'timeline', 'weeklyActivity', 'weeklyAverageIntensity',
        ));
    }

    /**
     * Build per-day food and symptom counts for the seven days starting at the given date.
     *
     * @param  Collection<int, FoodLog>  $foods
     * @param  Collection<int, SymptomLog>  $symptoms
     * @return array<int, array{label: string, date: string, isToday: bool, foods: int, symptoms: int, maxIntensity: int}>
     */
    private function weeklyActivity(CarbonImmutable $weekStart, Collection $foods, Collection $symptoms): array
    {
        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $foods, $symptoms): array {
            $day = $weekStart->addDays($offset);
            $daySymptoms = $symptoms->filter(fn (SymptomLog $log): bool => $log->time->isSameDay($day));

            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'isToday' => $day->isToday(),
                'foods' => $foods->filter(fn (FoodLog $log): bool => $log->time->isSameDay($day))->count(),
                'symptoms' => $daySymptoms->count(),
                'maxIntensity' => (int) $daySymptoms->max('intensity'),
            ];
        })->all();
    }
}
