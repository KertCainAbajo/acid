<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class WebAuthController extends Controller
{
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
        $this->storeDashboardToken($request, $user);

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
        $this->storeDashboardToken($request, $user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
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

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
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
        $this->storeDashboardToken($request, $user);

        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }

    private function storeDashboardToken(Request $request, User $user): void
    {
        $request->session()->put('api_token', $user->createToken('web-dashboard')->plainTextToken);
    }
}
