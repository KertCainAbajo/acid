<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FoodLogRequest;
use App\Http\Requests\SleepLogRequest;
use App\Http\Requests\SymptomLogRequest;
use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Services\InsightService;
use App\Services\SystemLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function __construct(
        private readonly InsightService $insightService,
        private readonly SystemLogService $systemLogService,
    ) {}

    public function storeFood(FoodLogRequest $request): JsonResponse
    {
        $log = $request->user()->foodLogs()->create($request->validated());
        $this->systemLogService->record($request, 'food_log_created', $log);
        $insight = $this->insightService->generate($request->user());

        return response()->json(['message' => 'Food log saved.', 'data' => $log, 'insight' => $insight], 201);
    }

    public function storeSymptom(SymptomLogRequest $request): JsonResponse
    {
        $log = $request->user()->symptomLogs()->create($request->validated());
        $this->systemLogService->record($request, 'symptom_log_created', $log);
        $insight = $this->insightService->generate($request->user());

        return response()->json(['message' => 'Symptom log saved.', 'data' => $log, 'insight' => $insight], 201);
    }

    public function storeSleep(SleepLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $existingLog = $request->user()->sleepLogs()->whereDate('logged_on', $data['logged_on'])->first();
        $log = $existingLog
            ? tap($existingLog)->update($data)
            : $request->user()->sleepLogs()->create($data);
        $this->systemLogService->record($request, $existingLog ? 'sleep_log_updated' : 'sleep_log_created', $log);
        $insight = $this->insightService->generate($request->user());

        return response()->json([
            'message' => $existingLog ? 'Sleep and stress log updated for that day.' : 'Sleep and stress log saved.',
            'data' => $log,
            'insight' => $insight,
        ], $existingLog ? 200 : 201);
    }

    public function updateFood(FoodLogRequest $request, FoodLog $foodLog): JsonResponse
    {
        Gate::authorize('update', $foodLog);

        return $this->updateEntry($request, $foodLog, $request->validated(), 'food_log_updated', 'Food log updated.');
    }

    public function destroyFood(Request $request, FoodLog $foodLog): JsonResponse
    {
        Gate::authorize('delete', $foodLog);

        return $this->deleteEntry($request, $foodLog, 'food_log_deleted', 'Food log deleted.');
    }

    public function updateSymptom(SymptomLogRequest $request, SymptomLog $symptomLog): JsonResponse
    {
        Gate::authorize('update', $symptomLog);

        return $this->updateEntry($request, $symptomLog, $request->validated(), 'symptom_log_updated', 'Symptom log updated.');
    }

    public function destroySymptom(Request $request, SymptomLog $symptomLog): JsonResponse
    {
        Gate::authorize('delete', $symptomLog);

        return $this->deleteEntry($request, $symptomLog, 'symptom_log_deleted', 'Symptom log deleted.');
    }

    public function updateSleep(SleepLogRequest $request, SleepLog $sleepLog): JsonResponse
    {
        Gate::authorize('update', $sleepLog);
        $data = $request->validated();

        $dayIsTaken = $request->user()->sleepLogs()
            ->whereDate('logged_on', $data['logged_on'])
            ->whereKeyNot($sleepLog->getKey())
            ->exists();
        if ($dayIsTaken) {
            throw ValidationException::withMessages(['logged_on' => 'You already have a sleep entry for that day. Edit that entry instead.']);
        }

        return $this->updateEntry($request, $sleepLog, $data, 'sleep_log_updated', 'Sleep and stress log updated.');
    }

    public function destroySleep(Request $request, SleepLog $sleepLog): JsonResponse
    {
        Gate::authorize('delete', $sleepLog);

        return $this->deleteEntry($request, $sleepLog, 'sleep_log_deleted', 'Sleep and stress log deleted.');
    }

    public function insights(Request $request): JsonResponse
    {
        $latest = $request->user()->insights()->latest('generated_at')->first();
        if (! $latest) {
            $latest = $this->insightService->generate($request->user());
        }

        return response()->json([
            'latest' => $latest,
            'history' => $request->user()->insights()->latest('generated_at')->limit(20)->get(),
        ]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $from = now()->subDays(30);
        $foods = $request->user()->foodLogs()->where('time', '>=', $from)->get();
        $symptoms = $request->user()->symptomLogs()->where('time', '>=', $from)->get();
        $triggers = $this->insightService->findTriggers($foods, $symptoms)->pluck('food')->values();

        return response()->json([
            'safe_foods' => FoodCategory::where('type', 'safe')->orderBy('name')->pluck('name')->values(),
            'avoid_foods' => FoodCategory::where('type', 'avoid')->orderBy('name')->pluck('name')->values(),
            'personalized_triggers' => $triggers,
            'note' => 'Recommendations are based on your logged patterns and are not a substitute for medical advice.',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);
        $user = $request->user();

        $foodLogs = $user->foodLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->when($to, fn ($query) => $query->where('time', '<=', $to))->latest('time')->get();
        $symptomLogs = $user->symptomLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->when($to, fn ($query) => $query->where('time', '<=', $to))->latest('time')->get();
        $sleepLogs = $user->sleepLogs()->when($from, fn ($query) => $query->whereDate('logged_on', '>=', $from->toDateString()))->when($to, fn ($query) => $query->whereDate('logged_on', '<=', $to->toDateString()))->latest('logged_on')->get();

        $timeline = collect()
            ->concat($foodLogs->map(fn (FoodLog $log) => ['type' => 'food', 'occurred_at' => $log->time, 'data' => $log]))
            ->concat($symptomLogs->map(fn (SymptomLog $log) => ['type' => 'symptom', 'occurred_at' => $log->time, 'data' => $log]))
            ->concat($sleepLogs->map(fn (SleepLog $log) => ['type' => 'sleep', 'occurred_at' => $log->logged_on, 'data' => $log]))
            ->sortByDesc('occurred_at')->values();

        return response()->json([
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'timeline' => $timeline,
            'food_logs' => $foodLogs,
            'symptom_logs' => $symptomLogs,
            'sleep_logs' => $sleepLogs,
        ]);
    }

    public function foodLogs(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);
        $logs = $request->user()->foodLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->when($to, fn ($query) => $query->where('time', '<=', $to))->latest('time')->paginate(25);

        return response()->json($logs);
    }

    public function symptomLogs(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);
        $logs = $request->user()->symptomLogs()->when($from, fn ($query) => $query->where('time', '>=', $from))->when($to, fn ($query) => $query->where('time', '<=', $to))->latest('time')->paginate(25);

        return response()->json($logs);
    }

    public function sleepLogs(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);
        $logs = $request->user()->sleepLogs()->when($from, fn ($query) => $query->whereDate('logged_on', '>=', $from->toDateString()))->when($to, fn ($query) => $query->whereDate('logged_on', '<=', $to->toDateString()))->latest('logged_on')->paginate(25);

        return response()->json($logs);
    }

    /**
     * Apply a correction to one of the patient's entries and refresh their insight.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateEntry(Request $request, FoodLog|SymptomLog|SleepLog $entry, array $data, string $action, string $message): JsonResponse
    {
        $entry->update($data);
        $this->systemLogService->record($request, $action, $entry, ['changes' => array_keys($entry->getChanges())]);

        return response()->json([
            'message' => $message,
            'data' => $entry->fresh(),
            'insight' => $this->insightService->generate($request->user()),
        ]);
    }

    /**
     * Remove one of the patient's entries and refresh their insight.
     */
    private function deleteEntry(Request $request, FoodLog|SymptomLog|SleepLog $entry, string $action, string $message): JsonResponse
    {
        $entry->delete();
        $this->systemLogService->record($request, $action, $entry);

        return response()->json([
            'message' => $message,
            'insight' => $this->insightService->generate($request->user()),
        ]);
    }

    private function dateRange(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null,
            isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null,
        ];
    }
}
