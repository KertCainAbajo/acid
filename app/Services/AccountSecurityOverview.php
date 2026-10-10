<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the "Account security" panels show for the signed-in user.
 */
class AccountSecurityOverview
{
    /**
     * Security events shown in a user's own sign-in history.
     *
     * @var list<string>
     */
    public const HISTORY_ACTIONS = [
        'user_logged_in', 'user_logged_out', 'login_failed', 'new_device_sign_in', 'account_locked', 'account_unlocked',
        'session_timed_out', 'session_blocked', 'sessions_revoked', 'two_factor_enabled', 'two_factor_disabled',
        'recovery_codes_regenerated', 'recovery_code_used', 'account_password_changed', 'user_registered',
    ];

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    /**
     * @return array{user: User, twoFactorEnabled: bool, twoFactorPending: bool, setupKey: ?string, setupUri: ?string, recoveryCodesLeft: int, newRecoveryCodes: ?array<int, string>, otherSessions: int, history: Collection<int, SystemLog>, score: int, checks: list<array{label: string, done: bool}>}
     */
    public function for(Request $request): array
    {
        $user = $request->user();
        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
        $otherSessions = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->count()
            : 0;

        $checks = [
            ['label' => 'Two-factor authentication is on', 'done' => $user->hasTwoFactorEnabled()],
            ['label' => 'Password has been changed since the account was set up', 'done' => $user->password_changed_at !== null || $user->systemLogs()->where('action', 'account_password_changed')->exists()],
            ['label' => 'Recovery codes available', 'done' => $user->hasTwoFactorEnabled() && count($user->two_factor_recovery_codes ?? []) > 0],
            ['label' => 'No other signed-in sessions you do not recognise', 'done' => $otherSessions === 0],
        ];

        return [
            'user' => $user,
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorPending' => $pending,
            'setupKey' => $pending ? trim(chunk_split($user->two_factor_secret, 4, ' ')) : null,
            'setupUri' => $pending ? $this->twoFactor->provisioningUri($user, $user->two_factor_secret) : null,
            'recoveryCodesLeft' => count($user->two_factor_recovery_codes ?? []),
            'newRecoveryCodes' => session('recovery_codes'),
            'otherSessions' => $otherSessions,
            'history' => SystemLog::query()
                ->whereIn('action', self::HISTORY_ACTIONS)
                ->where(fn ($query) => $query->where('actor_id', $user->id)->orWhere(fn ($target) => $target->where('target_type', $user->getMorphClass())->where('target_id', $user->id)))
                ->latest('id')
                ->limit(12)
                ->get(),
            'score' => (int) round(collect($checks)->where('done', true)->count() / count($checks) * 100),
            'checks' => $checks,
        ];
    }
}
