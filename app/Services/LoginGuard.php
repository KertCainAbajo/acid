<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Layer 1: account lockout after repeated failed sign-ins, plus security event logging for every sign-in attempt.
 */
class LoginGuard
{
    /** Failed attempts allowed before the account is locked. */
    public const MAX_ATTEMPTS = 10;

    /** How long a locked account stays locked. */
    public const LOCK_MINUTES = 15;

    public function __construct(private readonly SystemLogService $systemLogService) {}

    /**
     * The message to show when the account is temporarily locked, or null when sign-in may continue.
     */
    public function lockMessage(?User $user): ?string
    {
        if (! $user?->isLocked()) {
            return null;
        }

        $minutes = max(1, (int) ceil(now()->diffInSeconds($user->locked_until) / 60));

        return "This account is temporarily locked after too many failed sign-in attempts. Try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.';
    }

    public function recordFailure(Request $request, ?User $user, string $email, string $reason = 'password'): void
    {
        $this->systemLogService->record($request, 'login_failed', $user, ['email' => mb_strtolower($email), 'reason' => $reason]);

        if (! $user) {
            return;
        }

        $attempts = $user->failed_login_attempts + 1;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => now()->addMinutes(self::LOCK_MINUTES)])->save();
            $user->tokens()->delete();
            $this->systemLogService->record($request, 'account_locked', $user, ['minutes' => self::LOCK_MINUTES, 'attempts' => $attempts]);

            return;
        }

        $user->forceFill(['failed_login_attempts' => $attempts])->save();
    }

    /**
     * Reset the failure counter and record the sign-in, flagging browsers this account has not used before.
     */
    public function recordSuccess(Request $request, User $user, string $channel): void
    {
        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        $device = $this->deviceFingerprint($request);
        $isKnownDevice = SystemLog::where('target_type', $user->getMorphClass())
            ->where('target_id', $user->getKey())
            ->where('action', 'user_logged_in')
            ->get(['metadata'])
            ->contains(fn (SystemLog $log): bool => ($log->metadata['device'] ?? null) === $device);

        $this->systemLogService->record($request, 'user_logged_in', $user, ['channel' => $channel, 'device' => $device, 'browser' => $this->describeDevice($request)]);
        if (! $isKnownDevice && $this->hasSignedInBefore($user)) {
            $this->systemLogService->record($request, 'new_device_sign_in', $user, ['browser' => $this->describeDevice($request)]);
        }
    }

    public function deviceFingerprint(Request $request): string
    {
        return substr(hash('sha256', (string) $request->userAgent()), 0, 16);
    }

    /**
     * A friendly name such as "Chrome on Windows" for sign-in history.
     */
    public function describeDevice(Request $request): string
    {
        $agent = (string) $request->userAgent();
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'an unknown device',
        };

        return "{$browser} on {$platform}";
    }

    private function hasSignedInBefore(User $user): bool
    {
        return SystemLog::where('target_type', $user->getMorphClass())->where('target_id', $user->getKey())->where('action', 'user_logged_in')->count() > 1;
    }
}
