<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginGuard;
use App\Services\SystemLogService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly SystemLogService $systemLogService,
        private readonly LoginGuard $loginGuard,
    ) {}

    public function register(Request $request): JsonResponse
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
        ]);
        $this->systemLogService->record($request, 'user_registered', $user);

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $user->createToken('acid-reflux-api', ['*'], now()->addDays(30))->plainTextToken,
        ], 201);
    }

    public function login(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($lockMessage = $this->loginGuard->lockMessage($user)) {
            return response()->json(['message' => $lockMessage], 423);
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->loginGuard->recordFailure($request, $user, $data['email']);

            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account has been deactivated.'], 403);
        }

        if ($user->mustUseTwoFactor() && ! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => 'Set up two-factor authentication in the web app before using the API.'], 403);
        }

        if ($user->hasTwoFactorEnabled()) {
            $code = (string) ($data['two_factor_code'] ?? '');
            if ($code === '') {
                return response()->json(['message' => 'Enter the 6-digit code from your authenticator app.', 'two_factor_required' => true], 422);
            }

            $valid = $twoFactor->verify($user->two_factor_secret, $code, 'user-'.$user->id) || $twoFactor->useRecoveryCode($user, $code);
            if (! $valid) {
                $this->loginGuard->recordFailure($request, $user, $user->email, 'two_factor');

                return response()->json(['message' => 'That two-factor code is not valid.', 'two_factor_required' => true], 422);
            }
        }

        $this->loginGuard->recordSuccess($request, $user, 'api');

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $user->createToken('acid-reflux-api', ['*'], now()->addDays(30))->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->systemLogService->record($request, 'user_logged_out', $request->user());
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout successful.']);
    }
}
