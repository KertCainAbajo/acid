<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LogSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim($validated['q'] ?? '');
        $user = $request->user();
        $results = collect();

        if ($query !== '') {
            $searchTerm = "%{$query}%";

            $results = $this->searchFoodLogs($user->foodLogs(), $searchTerm)
                ->concat($this->searchSymptomLogs($user->symptomLogs(), $searchTerm))
                ->concat($this->searchSleepLogs($user->sleepLogs(), $searchTerm))
                ->sortByDesc('at')
                ->take(20)
                ->values();
        }

        return view('logs.search', [
            'query' => $query,
            'results' => $results,
            'user' => $user,
        ]);
    }

    private function searchFoodLogs(HasMany $foodLogs, string $searchTerm): Collection
    {
        return $foodLogs
            ->where(function (Builder $query) use ($searchTerm): void {
                $query->where('food_name', 'like', $searchTerm)
                    ->orWhere('category', 'like', $searchTerm)
                    ->orWhere('notes', 'like', $searchTerm);
            })
            ->latest('time')
            ->limit(12)
            ->get()
            ->map(fn ($log): array => [
                'kind' => 'Food',
                'title' => $log->food_name,
                'detail' => ucfirst($log->category).' · '.$log->time->format('M d, Y · g:i A'),
                'at' => $log->time,
                'tone' => 'food',
            ]);
    }

    private function searchSymptomLogs(HasMany $symptomLogs, string $searchTerm): Collection
    {
        return $symptomLogs
            ->where(function (Builder $query) use ($searchTerm): void {
                $query->where('symptom_type', 'like', $searchTerm)
                    ->orWhere('notes', 'like', $searchTerm);
            })
            ->latest('time')
            ->limit(12)
            ->get()
            ->map(fn ($log): array => [
                'kind' => 'Symptom',
                'title' => ucfirst($log->symptom_type),
                'detail' => 'Intensity '.$log->intensity.'/10 · '.$log->time->format('M d, Y · g:i A'),
                'at' => $log->time,
                'tone' => 'symptom',
            ]);
    }

    private function searchSleepLogs(HasMany $sleepLogs, string $searchTerm): Collection
    {
        return $sleepLogs
            ->where('notes', 'like', $searchTerm)
            ->latest('logged_on')
            ->limit(12)
            ->get()
            ->map(fn ($log): array => [
                'kind' => 'Sleep & stress',
                'title' => number_format($log->sleep_hours, 1).' hours of sleep',
                'detail' => 'Quality '.$log->sleep_quality.'/5 · Stress '.$log->stress_level.'/10 · '.$log->logged_on->format('M d, Y'),
                'at' => $log->logged_on,
                'tone' => 'sleep',
            ]);
    }
}
