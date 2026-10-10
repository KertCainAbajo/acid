<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodCategoryRequest;
use App\Models\FoodCategory;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages the general "usually gentle" and "common trigger" food guidance shown on every patient's Recommendations page.
 */
class AdminFoodCategoryController extends Controller
{
    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function index(): View
    {
        $categories = FoodCategory::orderBy('name')->get();

        return view('admin.food-guidance', [
            'title' => 'Food guidance',
            'safeFoods' => $categories->where('type', 'safe')->values(),
            'avoidFoods' => $categories->where('type', 'avoid')->values(),
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
        $foodCategory->update($request->validated());
        $this->systemLogService->record($request, 'food_category_updated', $foodCategory);

        return redirect()->route('admin.food-guidance')->with('status', "{$foodCategory->name} was updated.");
    }

    public function destroy(Request $request, FoodCategory $foodCategory): RedirectResponse
    {
        $foodCategory->delete();
        $this->systemLogService->record($request, 'food_category_deleted', $foodCategory);

        return redirect()->route('admin.food-guidance')->with('status', "{$foodCategory->name} was removed.");
    }
}
