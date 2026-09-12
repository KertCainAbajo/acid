<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly SystemLogService $systemLogService) {}

    public function register(Request $request): JsonResponse
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
        $this->systemLogService->record($request, 'user_registered', $user);

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $user->createToken('acid-reflux-api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account has been deactivated.'], 403);
        }

        $this->systemLogService->record($request, 'user_logged_in', $user);

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $user->createToken('acid-reflux-api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->systemLogService->record($request, 'user_logged_out', $request->user());
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout successful.']);
    }
}
