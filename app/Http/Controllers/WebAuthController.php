<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardTokenService;
use App\Services\LoginGuard;
use App\Services\SessionGuard;
use App\Services\SystemLogService;
use App\Services\TwoFactorService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class WebAuthController extends Controller
{
    public function __construct(
        private readonly DashboardTokenService $dashboardTokens,
        private readonly SystemLogService $systemLogService,
        private readonly LoginGuard $loginGuard,
        private readonly SessionGuard $sessionGuard,
    ) {}

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $failed = fn (string $message): RedirectResponse => back()->withInput($request->only('email'))->withErrors(['email' => $message]);

        if ($lockMessage = $this->loginGuard->lockMessage($user)) {
            return $failed($lockMessage);
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->loginGuard->recordFailure($request, $user, $credentials['email']);

            return $failed($this->loginGuard->lockMessage($user?->fresh()) ?? 'Those credentials do not match our records.');
        }

        if (! $user->is_active) {
            return $failed('This account has been deactivated.');
        }

        return $this->startSession($request, $user, $request->boolean('remember'), 'web');
    }

    public function showTwoFactorChallenge(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in expired. Please sign in again.']);
        }

        return view('auth.two-factor-challenge', ['title' => 'Two-factor verification']);
    }

    public function verifyTwoFactorChallenge(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in expired. Please sign in again.']);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'string', 'max:20'],
        ]);

        $usedRecoveryCode = filled($data['recovery_code'] ?? null) && $twoFactor->useRecoveryCode($user, $data['recovery_code']);
        $validCode = ! $usedRecoveryCode && filled($data['code'] ?? null) && $twoFactor->verify($user->two_factor_secret, $data['code'], 'user-'.$user->id);

        if (! $usedRecoveryCode && ! $validCode) {
            $this->loginGuard->recordFailure($request, $user, $user->email, 'two_factor');
            if ($lockMessage = $this->loginGuard->lockMessage($user->fresh())) {
                $request->session()->forget('login.pending');

                return redirect()->route('login')->withErrors(['email' => $lockMessage]);
            }

            return back()->withErrors(['code' => 'That code is not valid. Check your authenticator app and try again.']);
        }

        $pending = $request->session()->pull('login.pending');
        if ($usedRecoveryCode) {
            $this->systemLogService->record($request, 'recovery_code_used', $user, ['remaining' => count($user->fresh()->two_factor_recovery_codes ?? [])]);
        }

        return $this->completeLogin($request, $user, (bool) $pending['remember'], $pending['channel']);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create([
            ...$data,
            'role' => User::ROLE_PATIENT,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->sessionGuard->stamp($request);
        $this->systemLogService->record($request, 'user_registered', $user, ['channel' => 'web']);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->systemLogService->record($request, 'user_logged_out', $request->user(), ['channel' => 'web']);
        $this->dashboardTokens->revoke($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function googleRedirect(Request $request): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in is not configured yet. Add the Google OAuth values to your .env file.']);
        }

        // Google returns to the configured callback URL; starting on another host (e.g. 127.0.0.1 vs localhost)
        // would put the OAuth state in a different session cookie and the sign-in would always fail.
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($request->getSchemeAndHttpHost() !== $appUrl) {
            return redirect()->away($appUrl.route('google.redirect', [], false));
        }

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['email' => $request->query('error') === 'access_denied'
                ? 'Google sign-in was cancelled.'
                : 'Google refused the sign-in request ('.$request->query('error').'). Please try again.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            Log::warning('Google sign-in failed: the OAuth state did not match the session.', ['host' => $request->getHost()]);

            return redirect()->route('login')->withErrors(['email' => 'Your Google sign-in session expired. Please try again, and open the app at '.config('app.url').' so the sign-in can finish on the same address.']);
        } catch (RequestException $exception) {
            $googleError = json_decode((string) $exception->getResponse()?->getBody(), true) ?? [];
            Log::error('Google sign-in failed: Google rejected the token request.', [
                'status' => $exception->getResponse()?->getStatusCode(),
                'error' => $googleError['error'] ?? null,
                'description' => $googleError['error_description'] ?? null,
            ]);

            return redirect()->route('login')->withErrors(['email' => match ($googleError['error'] ?? null) {
                'redirect_uri_mismatch' => 'Google sign-in is misconfigured: the redirect URL does not match the one registered in Google Cloud.',
                'invalid_client', 'unauthorized_client' => 'Google sign-in is misconfigured: the client ID or secret is not valid.',
                default => 'Google sign-in could not be completed. Please try again.',
            }]);
        } catch (\Throwable $exception) {
            Log::error('Google sign-in failed.', ['exception' => $exception::class, 'message' => $exception->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'Google sign-in could not be completed. Please try again.']);
        }

        $email = $googleUser->getEmail();
        if (! $email || ($googleUser->getRaw()['email_verified'] ?? true) === false) {
            return redirect()->route('login')->withErrors(['email' => 'Your Google account must have a verified email address.']);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $googleUser->getName() ?: 'Google Patient',
                'password' => Hash::make(Str::random(48)),
                'role' => User::ROLE_PATIENT,
                'is_active' => true,
            ],
        );

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'This account has been deactivated.']);
        }

        if ($lockMessage = $this->loginGuard->lockMessage($user)) {
            return redirect()->route('login')->withErrors(['email' => $lockMessage]);
        }

        if ($user->wasRecentlyCreated) {
            Auth::login($user, true);
            $request->session()->regenerate();
            $this->sessionGuard->stamp($request);
            $this->systemLogService->record($request, 'user_registered', $user, ['channel' => 'google']);

            return redirect()->route('dashboard');
        }

        return $this->startSession($request, $user, true, 'google');
    }

    /**
     * Sign the user in, or send them to the two-factor challenge first when their account uses it.
     */
    private function startSession(Request $request, User $user, bool $remember, string $channel): RedirectResponse
    {
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.pending', [
                'id' => $user->id,
                'remember' => $remember,
                'channel' => $channel,
                'expires_at' => now()->addMinutes(5)->getTimestamp(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->completeLogin($request, $user, $remember, $channel);
    }

    private function completeLogin(Request $request, User $user, bool $remember, string $channel): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->sessionGuard->stamp($request);
        $this->loginGuard->recordSuccess($request, $user, $channel);

        if ($user->mustUseTwoFactor() && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.settings')->with('status', 'Set up two-factor authentication to finish securing your admin account.');
        }

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->intended(route('dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('login.pending');
        if (! $pending || $pending['expires_at'] < now()->getTimestamp()) {
            $request->session()->forget('login.pending');

            return null;
        }

        $user = User::find($pending['id']);

        return $user && $user->is_active && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
