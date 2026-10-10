<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layer 5: the security center where administrators watch for attacks and help locked-out users.
 */
class AdminSecurityController extends Controller
{
    /**
     * Security events shown in the admin feed.
     *
     * @var list<string>
     */
    public const EVENTS = [
        'login_failed', 'account_locked', 'account_unlocked', 'new_device_sign_in', 'session_blocked', 'session_timed_out',
        'sessions_revoked', 'two_factor_enabled', 'two_factor_disabled', 'recovery_code_used', 'recovery_codes_regenerated',
        'account_password_changed', 'patient_deactivated', 'patient_deleted',
    ];

    /** Failed sign-ins from one address within 24 hours that mark it as suspicious. */
    public const SUSPICIOUS_FAILURES = 5;

    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function index(): View
    {
        $dayAgo = now()->subDay();
        $failures = SystemLog::where('action', 'login_failed')->where('created_at', '>=', now()->subDays(7))->get(['ip_address', 'metadata', 'created_at']);
        $dayFailures = $failures->filter(fn (SystemLog $log): bool => $log->created_at->greaterThanOrEqualTo($dayAgo));
        $users = User::query()->get(['id', 'name', 'email', 'role', 'two_factor_secret', 'two_factor_confirmed_at', 'locked_until', 'is_active']);
        $patients = $users->where('role', User::ROLE_PATIENT);
        $admins = $users->where('role', User::ROLE_ADMIN);

        return view('admin.security', [
            'title' => 'Security center',
            'failedToday' => $dayFailures->count(),
            'failedWeek' => $failures->count(),
            'lockedAccounts' => $users->filter(fn (User $user): bool => $user->isLocked())->values(),
            'locksThisWeek' => SystemLog::where('action', 'account_locked')->where('created_at', '>=', now()->subDays(7))->count(),
            'twoFactor' => [
                'admins' => $admins->filter(fn (User $user): bool => $user->hasTwoFactorEnabled())->count(),
                'adminTotal' => $admins->count(),
                'patients' => $patients->filter(fn (User $user): bool => $user->hasTwoFactorEnabled())->count(),
                'patientTotal' => $patients->count(),
            ],
            'suspiciousIps' => $dayFailures->groupBy('ip_address')
                ->map(fn ($logs, $ip): array => [
                    'ip' => $ip ?: 'Unknown',
                    'failures' => $logs->count(),
                    'accounts' => $logs->pluck('metadata.email')->filter()->unique()->count(),
                    'last' => $logs->max('created_at'),
                ])
                ->filter(fn (array $row): bool => $row['failures'] >= self::SUSPICIOUS_FAILURES)
                ->sortByDesc('failures')->values(),
            'targetedAccounts' => $failures->groupBy(fn (SystemLog $log): string => (string) ($log->metadata['email'] ?? 'unknown'))
                ->map(fn ($logs, $email): array => ['email' => $email, 'failures' => $logs->count(), 'exists' => $users->contains('email', $email)])
                ->sortByDesc('failures')->take(5)->values(),
            'events' => SystemLog::with('actor:id,name,email,role')->whereIn('action', self::EVENTS)->latest('id')->limit(20)->get(),
            'newDevicesWeek' => SystemLog::where('action', 'new_device_sign_in')->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function unlock(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['locked_until' => null, 'failed_login_attempts' => 0])->save();
        $this->systemLogService->record($request, 'account_unlocked', $user);

        return back()->with('status', "{$user->name} can sign in again.");
    }
}
