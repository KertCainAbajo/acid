<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Http\Requests\UpdateAccountProfileRequest;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function edit(): View
    {
        $user = request()->user();

        return view('admin.settings', [
            'title' => 'Account settings',
            'user' => $user,
            'passwordChangedAt' => $user->systemLogs()->where('action', 'account_password_changed')->latest()->value('created_at'),
        ]);
    }

    public function updateProfile(UpdateAccountProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        $changes = array_keys($user->getDirty());

        if ($changes === []) {
            return redirect()->route('admin.settings')->with('status', 'No changes to save.');
        }

        $user->save();
        $this->systemLogService->record($request, 'account_profile_updated', $user, ['changes' => $changes]);

        return redirect()->route('admin.settings')->with('status', in_array('email', $changes, true)
            ? 'Profile saved. Use your new email address the next time you sign in.'
            : 'Profile saved.');
    }

    public function updatePassword(UpdateAccountPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        // Sign out every other device: API tokens are revoked and a fresh one is issued for this session.
        $user->tokens()->delete();
        $request->session()->forget(['api_token', 'api_token_id']);
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        $this->systemLogService->record($request, 'account_password_changed', $user);

        return redirect()->route('admin.settings')->with('status', 'Password changed. Other devices have been signed out.');
    }
}
