<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns system log action names into a readable label, a colour tone and an SVG icon for the admin views.
 */
class ActivityPresenter
{
    /**
     * @return array{label: string, tone: string, icon: string}
     */
    public static function describe(string $action): array
    {
        $entry = fn (string $prefix, string $noun): string => Str::ucfirst(Str::after($action, $prefix)).' '.$noun;

        $shield = '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/>';
        $lock = '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>';

        [$label, $tone, $icon] = match (true) {
            $action === 'login_failed' => ['Failed sign-in', 'red', '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>'],
            $action === 'account_locked' => ['Account locked', 'red', $lock],
            $action === 'account_unlocked' => ['Account unlocked', 'green', '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.9-1"/>'],
            $action === 'new_device_sign_in' => ['New device sign-in', 'sky', '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>'],
            $action === 'session_timed_out' => ['Session timed out', 'slate', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
            $action === 'session_blocked' => ['Session blocked', 'red', $shield.'<path d="m9 9 6 6M15 9l-6 6"/>'],
            $action === 'sessions_revoked' => ['Other sessions signed out', 'amber', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>'],
            $action === 'two_factor_enabled' => ['Two-factor turned on', 'green', $shield.'<path d="m9 12 2 2 4-4"/>'],
            $action === 'two_factor_disabled' => ['Two-factor turned off', 'amber', $shield],
            $action === 'recovery_code_used' => ['Recovery code used', 'amber', '<path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5"/>'],
            $action === 'recovery_codes_regenerated' => ['Recovery codes renewed', 'amber', '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>'],
            $action === 'user_logged_in' => ['Signed in', 'green', '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>'],
            $action === 'user_logged_out' => ['Signed out', 'slate', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>'],
            $action === 'user_registered' => ['New account', 'sky', '<circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0M19 8v6M16 11h6"/>'],
            str_starts_with($action, 'food_log_') => [$entry('food_log_', 'food entry'), 'orange', '<path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/>'],
            str_starts_with($action, 'symptom_log_') => [$entry('symptom_log_', 'symptom'), 'red', '<path d="M3 12h4l2-5 4 10 2-5h6"/>'],
            str_starts_with($action, 'sleep_log_') => [$entry('sleep_log_', 'sleep entry'), 'violet', '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>'],
            str_starts_with($action, 'food_category_') => [$entry('food_category_', 'food guidance'), 'green', '<path d="M12 3c1.6 2.2 5 4.5 5 8.4A5 5 0 0 1 7 11.4C7 8.1 9.6 6 12 3Z"/>'],
            $action === 'patient_deactivated' => ['Patient deactivated', 'red', '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>'],
            $action === 'patient_reactivated' => ['Patient reactivated', 'green', '<path d="M20 6 9 17l-5-5"/>'],
            $action === 'patient_deleted', $action === 'user_account_deleted' => ['Account deleted', 'red', '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>'],
            str_starts_with($action, 'account_') || str_starts_with($action, 'user_account_') => [Str::ucfirst(str_replace('_', ' ', $action)), 'amber', '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'],
            default => [Str::ucfirst(str_replace('_', ' ', $action)), 'slate', '<circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/>'],
        };

        return ['label' => $label, 'tone' => $tone, 'icon' => $icon];
    }
}
