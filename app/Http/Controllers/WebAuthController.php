<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardTokenService;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

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

    public function googleRedirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in is not configured yet. Add the Google OAuth values to your .env file.']);
        }

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
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
