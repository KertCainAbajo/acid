<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodCategoryRequest;
use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SymptomLog;
use App\Services\PatternAnalysisService;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Manages the general "usually gentle" and "common trigger" food guidance shown on every patient's Recommendations page.
 */
class AdminFoodCategoryController extends Controller
{
    /** How far back anonymised patient evidence looks. */
    public const EVIDENCE_DAYS = 60;

    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function index(): View
    {
        $categories = FoodCategory::orderBy('name')->get();
        $evidence = $this->patientEvidence();
        $listed = $categories->pluck('name')->map(fn (string $name): string => mb_strtolower($name));
        $isListed = fn (string $food): bool => $listed->contains(fn (string $name): bool => str_contains($food, $name) || str_contains($name, $food));
        $evidenceFor = fn (FoodCategory $category): ?array => $evidence
            ->filter(fn (array $item, string $food): bool => str_contains($food, mb_strtolower($category->name)) || str_contains(mb_strtolower($category->name), $food))
            ->reduce(fn (?array $carry, array $item): array => [
                'triggerPatients' => ($carry['triggerPatients'] ?? 0) + $item['triggerPatients'],
                'associations' => ($carry['associations'] ?? 0) + $item['associations'],
                'safeMeals' => ($carry['safeMeals'] ?? 0) + $item['safeMeals'],
                'safePatients' => ($carry['safePatients'] ?? 0) + $item['safePatients'],
            ]);

        return view('admin.food-guidance', [
            'title' => 'Food guidance',
            'safeFoods' => $categories->toBase()->where('type', 'safe')->values()->map(fn (FoodCategory $food): array => ['model' => $food, 'evidence' => $evidenceFor($food)]),
            'avoidFoods' => $categories->toBase()->where('type', 'avoid')->values()->map(fn (FoodCategory $food): array => ['model' => $food, 'evidence' => $evidenceFor($food)]),
            'suggestedTriggers' => $evidence
                ->filter(fn (array $item, string $food): bool => ($item['associations'] >= 2 || $item['triggerPatients'] >= 2) && ! $isListed($food))
                ->sortByDesc('associations')->take(5)->values(),
            'suggestedGentle' => $evidence
                ->filter(fn (array $item, string $food): bool => $item['associations'] === 0 && ($item['safeMeals'] >= 3 || $item['safePatients'] >= 2) && ! $isListed($food))
                ->sortByDesc('safeMeals')->take(5)->values(),
            'patientsWithData' => FoodLog::where('time', '>=', now()->subDays(self::EVIDENCE_DAYS))->distinct()->count('user_id'),
        ]);
    }

    public function store(FoodCategoryRequest $request): RedirectResponse
    {
        $category = FoodCategory::create($request->validated());
        $this->systemLogService->record($request, 'food_category_created', $category);

        return redirect()->route('admin.food-guidance')->with('status', "{$category->name} was added to ".($category->type === 'safe' ? 'Usually gentle' : 'Common triggers').'.');
    }

    public function update(FoodCategoryRequest $request, FoodCategory $foodCategory): RedirectResponse
    {
        $previousType = $foodCategory->type;
        $foodCategory->update($request->validated());
        $this->systemLogService->record($request, 'food_category_updated', $foodCategory);

        return redirect()->route('admin.food-guidance')->with('status', $previousType !== $foodCategory->type
            ? "{$foodCategory->name} was moved to ".($foodCategory->type === 'safe' ? 'Usually gentle' : 'Common triggers').'.'
            : "{$foodCategory->name} was updated.");
    }

    public function destroy(Request $request, FoodCategory $foodCategory): RedirectResponse
    {
        $foodCategory->delete();
        $this->systemLogService->record($request, 'food_category_deleted', $foodCategory);

        return redirect()->route('admin.food-guidance')->with('status', "{$foodCategory->name} was removed.");
    }

    /**
     * Anonymised, per-food totals across all patients: how often a food was followed by symptoms and how often it was eaten without any.
     *
     * @return Collection<string, array{name: string, triggerPatients: int, associations: int, safePatients: int, safeMeals: int}>
     */
    private function patientEvidence(): Collection
    {
        $since = now()->subDays(self::EVIDENCE_DAYS);
        $symptoms = SymptomLog::where('time', '>=', $since)->get(['user_id', 'time'])->toBase()->groupBy('user_id');

        return FoodLog::where('time', '>=', $since)->get(['user_id', 'food_name', 'time'])->toBase()
            ->map(function (FoodLog $meal) use ($symptoms): array {
                $followed = ($symptoms->get($meal->user_id) ?? collect())
                    ->filter(fn (SymptomLog $symptom): bool => $symptom->time->betweenIncluded($meal->time, $meal->time->copy()->addHours(PatternAnalysisService::MEAL_WINDOW_HOURS)))
                    ->count();

                return ['food' => mb_strtolower(trim($meal->food_name)), 'name' => trim($meal->food_name), 'user' => $meal->user_id, 'followed' => $followed];
            })
            ->groupBy('food')
            ->map(function (Collection $meals): array {
                $triggered = $meals->where('followed', '>', 0);
                $clean = $meals->where('followed', 0);

                return [
                    'name' => ucfirst($meals->first()['name']),
                    'triggerPatients' => $triggered->pluck('user')->unique()->count(),
                    'associations' => $triggered->sum('followed'),
                    'safePatients' => $clean->pluck('user')->unique()->diff($triggered->pluck('user'))->count(),
                    'safeMeals' => $clean->count(),
                ];
            });
    }
}
