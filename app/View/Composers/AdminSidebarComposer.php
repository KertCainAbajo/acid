<?php

namespace App\View\Composers;

use App\Models\FoodCategory;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\View\View;

/**
 * Supplies the live counts and security reminders shown in the admin sidebar.
 */
class AdminSidebarComposer
{
    public function compose(View $view): void
    {
        $admin = auth()->user();
        $passwordEverChanged = $admin?->systemLogs()->where('action', 'account_password_changed')->exists() ?? false;

        $view->with('sidebar', [
            'patients' => User::where('role', User::ROLE_PATIENT)->count(),
            'deactivated' => User::where('role', User::ROLE_PATIENT)->where('is_active', false)->count(),
            'foods' => FoodCategory::count(),
            'eventsToday' => SystemLog::whereDate('created_at', today())->count(),
            'securityReminder' => match (true) {
                $admin === null => null,
                str_ends_with((string) $admin->email, '@example.com') => 'Replace the placeholder email address',
                ! $passwordEverChanged => 'Change the setup password',
                default => null,
            },
        ]);
    }
}
