<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    /** Shown whether or not the email has an account, so the form cannot be used to discover who is registered. */
    public const LINK_SENT_MESSAGE = 'If an account exists for that email, a password reset link is on its way. It expires in 60 minutes.';

    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::where('email', $data['email'])->first();

        if ($user?->is_active) {
            try {
                $sent = Password::sendResetLink(['email' => $user->email]) === Password::ResetLinkSent;
            } catch (Throwable $exception) {
                // A mail provider outage or bad SMTP credentials must not turn into an error page that reveals the account exists.
                report($exception);
                $sent = false;
            }
            $this->systemLogService->record($request, 'password_reset_requested', $user, ['sent' => $sent]);
        }

        return back()->with('status', self::LINK_SENT_MESSAGE)->with('reset_email', $data['email']);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = User::where('email', (string) $request->input('email'))->first();
        $passwordRule = $user?->isAdmin()
            ? PasswordRule::min(10)->letters()->mixedCase()->numbers()
            : PasswordRule::min(8)->letters()->numbers();

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', $passwordRule],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => $password,
                    'password_changed_at' => now(),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->setRememberToken(Str::random(60));
                $user->save();

                // Anyone who was using the old password is signed out everywhere.
                $user->tokens()->delete();
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }

                $this->systemLogService->record($request, 'password_reset_completed', $user);
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Request a new one.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password.');
    }
}
