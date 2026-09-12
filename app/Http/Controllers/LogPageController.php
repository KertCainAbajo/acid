<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LogPageController extends Controller
{
    public function food(): View
    {
        return $this->page('food', 'Food Intake Logger');
    }

    public function symptom(): View
    {
        return $this->page('symptom', 'Symptom Tracker');
    }

    public function sleep(): View
    {
        return $this->page('sleep', 'Sleep & Stress');
    }

    private function page(string $type, string $title): View
    {
        $user = request()->user();
        $recentLogs = match ($type) {
            'food' => $user->foodLogs()->latest('time')->limit(8)->get(),
            'symptom' => $user->symptomLogs()->latest('time')->limit(8)->get(),
            default => $user->sleepLogs()->latest('logged_on')->limit(8)->get(),
        };

        return view('logs.create', compact('type', 'title', 'recentLogs'));
    }
}
