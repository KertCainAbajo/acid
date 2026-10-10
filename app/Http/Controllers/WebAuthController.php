<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardTokenService;
use App\Services\SystemLogService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class WebAuthController extends Controller
{
    public function __construct(
        private readonly DashboardTokenService $dashboardTokens,
        private readonly SystemLogService $systemLogService,
    ) {}

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        if (! $user->is_active) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This account has been deactivated.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->systemLogService->record($request, 'user_logged_in', $user, ['channel' => 'web']);

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            ...$data,
            'role' => User::ROLE_PATIENT,
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
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

        Auth::login($user, true);
        $request->session()->regenerate();
        $this->systemLogService->record($request, $user->wasRecentlyCreated ? 'user_registered' : 'user_logged_in', $user, ['channel' => 'google']);

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }
}
