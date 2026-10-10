<?php

namespace App\Http\Controllers;

use App\Services\AccountSecurityOverview;
use App\Services\SystemLogService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Account security actions shared by patients and administrators: two-factor authentication, sessions and passwords.
 */
class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly SystemLogService $systemLogService,
    ) {}

    public function show(Request $request, AccountSecurityOverview $overview): View
    {
        return view('security.show', ['title' => 'Account security', ...$overview->for($request)]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasTwoFactorEnabled()) {
            return back()->with('status', 'Two-factor authentication is already on.');
        }

        $user->forceFill(['two_factor_secret' => $this->twoFactor->generateSecret(), 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();

        return back()->with('status', 'Add the key to your authenticator app, then enter the 6-digit code it shows.');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $code = $request->validateWithBag('twoFactor', ['code' => ['required', 'string', 'max:10']])['code'];

        if ($user->two_factor_secret === null || $user->two_factor_confirmed_at !== null) {
            return back();
        }
        if (! $this->twoFactor->verify($user->two_factor_secret, $code, 'user-'.$user->id)) {
            throw ValidationException::withMessages(['code' => 'That code is not valid. Make sure your phone\'s time is correct and try the newest code.'])->errorBag('twoFactor');
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        $this->systemLogService->record($request, 'two_factor_enabled', $user);

        return back()->with('status', 'Two-factor authentication is on. Save your recovery codes somewhere safe.')->with('recovery_codes', $codes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->mustUseTwoFactor()) {
            return back()->withErrors(['code' => 'Administrators must keep two-factor authentication on.'], 'twoFactor');
        }

        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']], ['current_password.current_password' => 'Your password is incorrect.']);
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        $this->systemLogService->record($request, 'two_factor_disabled', $user);

        return back()->with('status', 'Two-factor authentication is off. Your account is now protected by your password only.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']], ['current_password.current_password' => 'Your password is incorrect.']);
        if (! $user->hasTwoFactorEnabled()) {
            return back();
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $this->systemLogService->record($request, 'recovery_codes_regenerated', $user);

        return back()->with('status', 'New recovery codes created. Your old codes no longer work.')->with('recovery_codes', $codes);
    }

    public function signOutOtherSessions(Request $request): RedirectResponse
    {
        $request->validateWithBag('sessions', ['current_password' => ['required', 'current_password']], ['current_password.current_password' => 'Your password is incorrect.']);
        $removed = $this->endOtherSessions($request);
        $this->systemLogService->record($request, 'sessions_revoked', $request->user(), ['sessions' => $removed]);

        return back()->with('status', $removed ? "Signed out of {$removed} other session".($removed === 1 ? '' : 's').'.' : 'There were no other sessions to sign out.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rules = $user->isAdmin() ? Password::min(10)->letters()->mixedCase()->numbers() : Password::min(8)->letters()->numbers();
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', $rules],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a new password that is different from your current one.',
        ]);

        $user->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();
        $this->endOtherSessions($request);
        $this->systemLogService->record($request, 'account_password_changed', $user);

        return back()->with('status', 'Password changed. You were signed out on every other device.');
    }

    /**
     * Sign the user out everywhere except this browser. Returns how many other sessions were ended.
     */
    private function endOtherSessions(Request $request): int
    {
        $user = $request->user();
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        $user->tokens()->where('id', '!=', $request->session()->get('api_token_id'))->delete();
        $request->session()->regenerate();

        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
    }
}
