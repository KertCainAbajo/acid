<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FoodCategoryRequest;
use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\Insight;
use App\Models\SymptomLog;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\InsightService;
use App\Services\SystemLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly InsightService $insightService,
        private readonly SystemLogService $systemLogService,
    ) {}

    public function users(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')->paginate(25);

        return response()->json($users);
    }

    public function analytics(): JsonResponse
    {
        $latestInsights = Insight::query()->whereIn('id', function ($query) {
            $query->selectRaw('MAX(id)')->from('insights')->groupBy('user_id');
        })->get();

        $foods = FoodLog::where('time', '>=', now()->subDays(30))->get();
        $symptoms = SymptomLog::where('time', '>=', now()->subDays(30))->get();
        $triggers = $foods->groupBy('user_id')->flatMap(fn ($userFoods, $userId) => $this->insightService->findTriggers($userFoods, $symptoms->where('user_id', $userId))
        )->groupBy('food')->map(fn ($matches, $food) => [
            'food' => $food,
            'associations' => $matches->sum('count'),
        ])->sortByDesc('associations')->values()->take(10);

        return response()->json([
            'total_users' => User::count(),
            'total_patients' => User::where('role', User::ROLE_PATIENT)->count(),
            'active_patients' => User::where('role', User::ROLE_PATIENT)->where('is_active', true)->count(),
            'risk_level_distribution' => [
                'low' => $latestInsights->where('risk_level', 'low')->count(),
                'medium' => $latestInsights->where('risk_level', 'medium')->count(),
                'high' => $latestInsights->where('risk_level', 'high')->count(),
            ],
            'most_common_triggers' => $triggers,
            'generated_at' => now(),
        ]);
    }

    public function storeCategory(FoodCategoryRequest $request): JsonResponse
    {
        $category = FoodCategory::create($request->validated());
        $this->systemLogService->record($request, 'food_category_created', $category);

        return response()->json([
            'message' => 'Food category created.',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(FoodCategoryRequest $request, FoodCategory $foodCategory): JsonResponse
    {
        $foodCategory->update($request->validated());
        $this->systemLogService->record($request, 'food_category_updated', $foodCategory);

        return response()->json(['message' => 'Food category updated.', 'data' => $foodCategory->fresh()]);
    }

    public function deleteCategory(Request $request, FoodCategory $foodCategory): JsonResponse
    {
        $foodCategory->delete();
        $this->systemLogService->record($request, 'food_category_deleted', $foodCategory);

        return response()->json(['message' => 'Food category deleted.']);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'string', 'in:patient,admin'],
        ]);

        if ($user->is($request->user()) && (($data['is_active'] ?? true) === false || ($data['role'] ?? $user->role) !== User::ROLE_ADMIN)) {
            return response()->json(['message' => 'You cannot deactivate or demote your own admin account.'], 422);
        }

        $user->update($data);
        $this->systemLogService->record($request, 'user_account_updated', $user, ['changes' => array_keys($data)]);

        return response()->json(['message' => 'User account updated.', 'data' => $user->fresh()]);
    }

    public function deleteUser(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'You cannot delete your own admin account.'], 422);
        }

        $user->delete();
        $this->systemLogService->record($request, 'user_account_deleted', null, ['deleted_user_id' => $user->id]);

        return response()->json(['message' => 'User account deleted.']);
    }

    public function systemLogs(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = SystemLog::query()
            ->with('actor:id,name,email')
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(isset($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
