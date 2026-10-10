<?php

namespace App\Http\Middleware;

use App\Services\DashboardTokenService;
use App\Services\SessionGuard;
use App\Services\SystemLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Layer 3: signs out idle sessions and sessions used from a different browser, and makes administrators finish setting up two-factor authentication.
 */
class EnsureSecureSession
{
    /**
     * Routes an administrator may use before two-factor authentication is set up.
     *
     * @var list<string>
     */
    private const TWO_FACTOR_SETUP_ROUTES = ['admin.settings', 'admin.settings.*', 'security.*', 'logout'];

    public function __construct(
        private readonly SessionGuard $sessionGuard,
        private readonly DashboardTokenService $dashboardTokens,
        private readonly SystemLogService $systemLogService,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($violation = $this->sessionGuard->violation($request, $user)) {
            $this->systemLogService->record($request, $violation === 'idle' ? 'session_timed_out' : 'session_blocked', $user);
            $this->dashboardTokens->revoke($request);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $violation === 'idle'
                ? "You were signed out after {$user->idleTimeoutMinutes()} minutes of inactivity. Please sign in again."
                : 'For your security you were signed out because this session was used from a different browser.']);
        }

        if ($user->mustUseTwoFactor() && ! $user->hasTwoFactorEnabled() && ! $request->routeIs(...self::TWO_FACTOR_SETUP_ROUTES)) {
            return redirect()->route('admin.settings')->with('status', 'Set up two-factor authentication to finish securing your admin account.');
        }

        return $next($request);
    }
}
