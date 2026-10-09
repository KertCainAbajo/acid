<?php

namespace App\Http\Middleware;

use App\Services\DashboardTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out deactivated accounts and keeps the web pages' API token valid for active ones.
 */
class EnsureActiveWebSession
{
    public function __construct(private readonly DashboardTokenService $dashboardTokens) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            $this->dashboardTokens->revoke($request);
            $user->tokens()->where('name', DashboardTokenService::TOKEN_NAME)->delete();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been deactivated.']);
        }

        $this->dashboardTokens->ensure($request, $user);

        return $next($request);
    }
}
