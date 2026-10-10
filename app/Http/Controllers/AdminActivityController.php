<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminActivityController extends Controller
{
    /**
     * Action prefixes for each filter group.
     *
     * @var array<string, array{label: string, prefixes: list<string>}>
     */
    public const GROUPS = [
        'sign-ins' => ['label' => 'Sign-ins & sign-ups', 'prefixes' => ['user_logged_in', 'user_logged_out', 'user_registered']],
        'entries' => ['label' => 'Patient entries', 'prefixes' => ['food_log_', 'symptom_log_', 'sleep_log_']],
        'accounts' => ['label' => 'Account changes', 'prefixes' => ['patient_', 'account_', 'user_account_']],
        'guidance' => ['label' => 'Food guidance', 'prefixes' => ['food_category_']],
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $weekAgo = now()->subDays(7);
        $lastDay = SystemLog::where('created_at', '>=', now()->subHours(24))->pluck('created_at');
        $weekActions = SystemLog::where('created_at', '>=', $weekAgo)->pluck('action');

        return view('admin.activity', [
            'title' => 'System activity',
            'filters' => $filters,
            'groups' => self::GROUPS,
            'activities' => $this->query($filters)->paginate(25)->withQueryString(),
            'todayCount' => SystemLog::whereDate('created_at', today())->count(),
            'weekCount' => $weekActions->count(),
            'groupCounts' => collect(self::GROUPS)->map(fn (array $group): int => $weekActions
                ->filter(fn (string $action): bool => collect($group['prefixes'])->contains(fn (string $prefix): bool => str_starts_with($action, $prefix)))
                ->count())->all(),
            'hourly' => collect(range(23, 0))->map(function (int $hoursAgo) use ($lastDay): array {
                $start = now()->subHours($hoursAgo + 1);
                $end = now()->subHours($hoursAgo);

                return ['hour' => $end->format('g A'), 'count' => $lastDay->filter(fn ($time): bool => $time > $start && $time <= $end)->count()];
            })->all(),
            'topActors' => SystemLog::query()
                ->where('created_at', '>=', $weekAgo)
                ->whereNotNull('actor_id')
                ->selectRaw('actor_id, COUNT(*) as total')
                ->groupBy('actor_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('actor:id,name,email,role')
                ->get(),
            'security' => [
                'signInsToday' => SystemLog::where('action', 'user_logged_in')->whereDate('created_at', today())->count(),
                'newAccounts' => SystemLog::where('action', 'user_registered')->where('created_at', '>=', $weekAgo)->count(),
                'accountChanges' => SystemLog::where(fn (Builder $query) => $query->where('action', 'like', 'account_%')->orWhere('action', 'like', 'patient_%'))->where('created_at', '>=', $weekAgo)->count(),
                'uniqueIps' => SystemLog::where('created_at', '>=', $weekAgo)->whereNotNull('ip_address')->distinct()->count('ip_address'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $activities = $this->query($this->filters($request))->limit(5000)->get();

        return response()->streamDownload(function () use ($activities): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Time', 'Action', 'Person', 'Email', 'Target', 'IP address']);
            foreach ($activities as $activity) {
                fputcsv($output, [
                    $activity->created_at->toDateTimeString(),
                    $activity->action,
                    $activity->actor?->name ?? 'System',
                    $activity->actor?->email ?? '',
                    $activity->target_type ? class_basename($activity->target_type).' #'.$activity->target_id : '',
                    $activity->ip_address,
                ]);
            }
            fclose($output);
        }, 'refluxcare-activity-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{q: string, group: string, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', Rule::in(['all', ...array_keys(self::GROUPS)])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'q' => trim($validated['q'] ?? ''),
            'group' => $validated['group'] ?? 'all',
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    /**
     * @param  array{q: string, group: string, from: ?string, to: ?string}  $filters
     * @return Builder<SystemLog>
     */
    private function query(array $filters): Builder
    {
        return SystemLog::query()
            ->with('actor:id,name,email,role')
            ->when($filters['group'] !== 'all', fn (Builder $query) => $query->where(function (Builder $actions) use ($filters): void {
                foreach (self::GROUPS[$filters['group']]['prefixes'] as $prefix) {
                    $actions->orWhere('action', 'like', $prefix.'%');
                }
            }))
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(fn (Builder $search) => $search
                ->where('action', 'like', '%'.str_replace(' ', '_', $filters['q']).'%')
                ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', "%{$filters['q']}%")->orWhere('email', 'like', "%{$filters['q']}%"))))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id');
    }
}
