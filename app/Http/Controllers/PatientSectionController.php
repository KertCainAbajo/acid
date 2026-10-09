<?php

namespace App\Http\Controllers;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\User;
use App\Services\InsightService;
use App\Services\PatternAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientSectionController extends Controller
{
    public function insights(PatternAnalysisService $patternAnalysis, InsightService $insightService): View
    {
        $user = request()->user();
        $latestInsight = $user->insights()->latest('generated_at')->first() ?? $insightService->generate($user);

        return view('sections.insights', [
            'title' => 'AI Pattern Insights',
            'analysis' => $patternAnalysis->analyze($user),
            'latestInsight' => $latestInsight,
            'scoreHistory' => $user->insights()->whereNotNull('risk_score')->latest('generated_at')->limit(12)->get()->reverse()->values(),
        ]);
    }

    public function refreshInsights(InsightService $insightService): RedirectResponse
    {
        $insightService->generate(request()->user());

        return redirect()->route('insights')->with('status', 'Your analysis was refreshed with your latest logs.');
    }

    public function recommendations(PatternAnalysisService $patternAnalysis): View
    {
        $analysis = $patternAnalysis->analyze(request()->user());
        $personalFoods = collect($analysis['foods'])->keyBy(fn (array $food): string => mb_strtolower($food['name']));
        $withPersonalResult = fn (Collection $categories): Collection => $categories->map(fn (FoodCategory $category): array => [
            'name' => $category->name,
            'personal' => $personalFoods->first(fn (array $food, string $name): bool => str_contains($name, mb_strtolower($category->name)) || str_contains(mb_strtolower($category->name), $name)),
        ]);

        return view('sections.recommendations', [
            'title' => 'Food Recommendations',
            'analysis' => $analysis,
            'suspectedTriggers' => collect($analysis['foods'])->where('confidence', '!=', 'clear')->values(),
            'wellTolerated' => collect($analysis['foods'])->where('confidence', 'clear')->sortByDesc('eaten')->values(),
            'safeFoods' => $withPersonalResult(FoodCategory::where('type', 'safe')->orderBy('name')->get()),
            'avoidFoods' => $withPersonalResult(FoodCategory::where('type', 'avoid')->orderBy('name')->get()),
        ]);
    }

    public function history(): View
    {
        $user = request()->user();
        $today = CarbonImmutable::today();
        $timeline = $this->timeline($user, $today->subDays(90))->take(150)->values();
        $activeDays = $this->timeline($user, $today->subDays(35))->map(fn (array $event): string => $event['at']->toDateString())->countBy();

        $streak = 0;
        for ($day = $today; $activeDays->has($day->toDateString()); $day = $day->subDay()) {
            $streak++;
        }

        return view('sections.history', [
            'title' => 'Health History & Profile',
            'user' => $user,
            'timeline' => $timeline,
            'foodCount' => $user->foodLogs()->count(),
            'symptomCount' => $user->symptomLogs()->count(),
            'sleepCount' => $user->sleepLogs()->count(),
            'streak' => $streak,
            'heatmap' => collect(range(34, 0))->map(fn (int $daysAgo): array => [
                'date' => $today->subDays($daysAgo),
                'count' => $activeDays->get($today->subDays($daysAgo)->toDateString(), 0),
            ])->all(),
        ]);
    }

    public function exportHistory(): StreamedResponse
    {
        $user = request()->user();
        $rows = $this->timeline($user)->sortBy('at');

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Type', 'Date', 'Time', 'Entry', 'Details', 'Notes']);
            foreach ($rows as $row) {
                fputcsv($output, [$row['kind'], $row['at']->toDateString(), $row['kind'] === 'Sleep' ? '' : $row['at']->format('H:i'), $row['title'], $row['detail'], $row['notes'] ?? '']);
            }
            fclose($output);
        }, 'refluxcare-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * All of the patient's food, symptom and sleep entries as one list, newest first.
     *
     * @return Collection<int, array{kind: string, title: string, detail: string, notes: ?string, at: CarbonImmutable, tone: string}>
     */
    private function timeline(User $user, ?CarbonImmutable $from = null): Collection
    {
        $foods = $user->foodLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->get();
        $symptoms = $user->symptomLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->get();
        $sleepLogs = $user->sleepLogs()->when($from, fn ($query) => $query->whereDate('logged_on', '>=', $from->toDateString()))->get();

        return collect()
            ->concat($foods->map(fn (FoodLog $log): array => [
                'kind' => 'Food',
                'title' => $log->food_name,
                'detail' => ucfirst($log->category),
                'notes' => $log->notes,
                'at' => CarbonImmutable::instance($log->time),
                'tone' => 'food',
            ]))
            ->concat($symptoms->map(fn (SymptomLog $log): array => [
                'kind' => 'Symptom',
                'title' => ucfirst($log->symptom_type),
                'detail' => "Intensity {$log->intensity}/10",
                'notes' => $log->notes,
                'at' => CarbonImmutable::instance($log->time),
                'tone' => 'symptom',
            ]))
            ->concat($sleepLogs->map(fn (SleepLog $log): array => [
                'kind' => 'Sleep',
                'title' => number_format($log->sleep_hours, 1).' hours of sleep',
                'detail' => "Quality {$log->sleep_quality}/5 · Stress {$log->stress_level}/10",
                'notes' => $log->notes,
                'at' => CarbonImmutable::instance($log->logged_on),
                'tone' => 'sleep',
            ]))
            ->sortByDesc('at')
            ->values();
    }
}
