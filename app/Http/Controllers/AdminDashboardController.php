<?php

namespace App\Http\Controllers;

use App\Models\FoodCategory;
use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'patientCount' => User::where('role', User::ROLE_PATIENT)->count(),
            'activePatientCount' => User::where('role', User::ROLE_PATIENT)->where('is_active', true)->count(),
            'foodLogCount' => FoodLog::count(),
            'symptomLogCount' => SymptomLog::count(),
            'sleepLogCount' => SleepLog::count(),
            'categoryCount' => FoodCategory::count(),
            'recentPatients' => User::where('role', User::ROLE_PATIENT)
                ->latest()
                ->limit(8)
                ->get(['id', 'name', 'email', 'is_active', 'created_at']),
            'recentActivity' => SystemLog::with('actor:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'actor_id', 'action', 'created_at']),
        ]);
    }

    public function patients(): View
    {
        return view('admin.patients', [
            'patients' => User::where('role', User::ROLE_PATIENT)
                ->latest()
                ->limit(100)
                ->get(['id', 'name', 'email', 'is_active', 'created_at']),
        ]);
    }

    public function activity(): View
    {
        return view('admin.activity', [
            'activities' => SystemLog::with('actor:id,name,email')
                ->latest()
                ->limit(100)
                ->get(['id', 'actor_id', 'action', 'target_type', 'target_id', 'created_at']),
        ]);
    }
}
