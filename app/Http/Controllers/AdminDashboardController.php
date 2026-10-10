<?php

namespace App\Http\Controllers;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\Insight;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\InsightService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(InsightService $insightService): View
    {
        $today = CarbonImmutable::today();
        $weekStart = $today->subDays(6);
        $previousWeekStart = $today->subDays(13);
        $chartStart = $today->subDays(13);

        $dailyFoods = $this->dailyCounts(FoodLog::query(), 'time', $chartStart);
        $dailySymptoms = $this->dailyCounts(SymptomLog::query(), 'time', $chartStart);
        $dailySleep = $this->dailyCounts(SleepLog::query(), 'logged_on', $chartStart);
        $activity = collect(range(0, 13))->map(function (int $offset) use ($chartStart, $dailyFoods, $dailySymptoms, $dailySleep): array {
            $day = $chartStart->addDays($offset);
            $key = $day->toDateString();

            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'isToday' => $day->isToday(),
                'foods' => (int) $dailyFoods->get($key, 0),
                'symptoms' => (int) $dailySymptoms->get($key, 0),
                'sleep' => (int) $dailySleep->get($key, 0),
            ];
        });
        $entriesThisWeek = $activity->slice(7)->sum(fn (array $day): int => $day['foods'] + $day['symptoms'] + $day['sleep']);
        $entriesLastWeek = $activity->take(7)->sum(fn (array $day): int => $day['foods'] + $day['symptoms'] + $day['sleep']);

        $latestInsights = Insight::query()
            ->whereIn('id', fn ($query) => $query->selectRaw('MAX(id)')->from('insights')->groupBy('user_id'))
            ->whereHas('user', fn ($query) => $query->where('role', User::ROLE_PATIENT))
            ->get(['id', 'user_id', 'risk_level', 'risk_score']);

        $patients = User::where('role', User::ROLE_PATIENT);
        $patientCount = (clone $patients)->count();
        $activeThisWeek = collect()
            ->concat(FoodLog::where('time', '>=', $weekStart)->distinct()->pluck('user_id'))
            ->concat(SymptomLog::where('time', '>=', $weekStart)->distinct()->pluck('user_id'))
            ->concat(SleepLog::whereDate('logged_on', '>=', $weekStart->toDateString())->distinct()->pluck('user_id'))
            ->unique()
            ->count();

        $recentPatients = (clone $patients)
            ->withCount(['foodLogs', 'symptomLogs', 'sleepLogs'])
            ->withMax('foodLogs', 'time')
            ->withMax('symptomLogs', 'time')
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'is_active', 'created_at']);

        $monthFoods = FoodLog::where('time', '>=', $today->subDays(30))->get();
        $monthSymptoms = SymptomLog::where('time', '>=', $today->subDays(30))->get();

        return view('admin.dashboard', [
            'title' => 'Admin dashboard',
            'patientCount' => $patientCount,
            'activePatientCount' => (clone $patients)->where('is_active', true)->count(),
            'activeThisWeek' => $activeThisWeek,
            'newPatientsThisWeek' => (clone $patients)->where('created_at', '>=', $weekStart)->count(),
            'newPatientsLastWeek' => (clone $patients)->whereBetween('created_at', [$previousWeekStart, $weekStart])->count(),
            'foodLogCount' => FoodLog::count(),
            'symptomLogCount' => SymptomLog::count(),
            'sleepLogCount' => SleepLog::count(),
            'categoryCount' => FoodCategory::count(),
            'entriesThisWeek' => $entriesThisWeek,
            'entriesLastWeek' => $entriesLastWeek,
            'activity' => $activity->all(),
            'riskDistribution' => [
                'low' => $latestInsights->where('risk_level', 'low')->count(),
                'medium' => $latestInsights->where('risk_level', 'medium')->count(),
                'high' => $latestInsights->where('risk_level', 'high')->count(),
                'none' => max(0, $patientCount - $latestInsights->count()),
            ],
            'recentPatients' => $recentPatients,
            'recentActivity' => SystemLog::with('actor:id,name,role')->latest()->limit(8)->get(['id', 'actor_id', 'action', 'created_at']),
            'commonTriggers' => $monthFoods->groupBy('user_id')
                ->flatMap(fn (Collection $foods, int $userId): Collection => $insightService->findTriggers($foods, $monthSymptoms->where('user_id', $userId))
                    ->map(fn (array $trigger): array => [...$trigger, 'user_id' => $userId]))
                ->groupBy(fn (array $trigger): string => mb_strtolower($trigger['food']))
                ->map(fn (Collection $matches): array => [
                    'food' => $matches->first()['food'],
                    'associations' => $matches->sum('count'),
                    'patients' => $matches->pluck('user_id')->unique()->count(),
                ])
                ->sortByDesc('associations')
                ->take(5)
                ->values(),
        ]);
    }

    /**
     * Number of entries per calendar day since the given date, keyed by Y-m-d.
     *
     * @return Collection<string, int>
     */
    private function dailyCounts(Builder $query, string $column, CarbonImmutable $from): Collection
    {
        return $query->where($column, '>=', $from->toDateString())
            ->selectRaw("DATE({$column}) as day, COUNT(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day');
    }
}
