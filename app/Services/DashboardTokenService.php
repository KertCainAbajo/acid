<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Manages the short-lived Sanctum token that the web pages use to call the JSON API.
 */
class DashboardTokenService
{
    public const TOKEN_NAME = 'web-dashboard';

    /**
     * Tokens are reissued when they are this close to expiring, so an open page keeps working.
     */
    private const RENEW_WITHIN_MINUTES = 15;

    /**
     * Make sure the session holds a valid, unexpired dashboard token for the user.
     */
    public function ensure(Request $request, User $user): void
    {
        $token = $this->currentToken($request);

        if ($token && $token->tokenable_id === $user->getKey() && $token->expires_at?->isAfter(now()->addMinutes(self::RENEW_WITHIN_MINUTES))) {
            return;
        }

        $token?->delete();
        $newToken = $user->createToken(self::TOKEN_NAME, ['*'], now()->addMinutes($this->lifetimeInMinutes()));

        $request->session()->put([
            'api_token' => $newToken->plainTextToken,
            'api_token_id' => $newToken->accessToken->getKey(),
        ]);
    }

    /**
     * Delete the session's dashboard token so it cannot be reused after logout.
     */
    public function revoke(Request $request): void
    {
        $this->currentToken($request)?->delete();
        $request->session()->forget(['api_token', 'api_token_id']);
    }

    private function currentToken(Request $request): ?PersonalAccessToken
    {
        $tokenId = $request->session()->get('api_token_id');

        return $tokenId ? PersonalAccessToken::find($tokenId) : null;
    }

    private function lifetimeInMinutes(): int
    {
        return max(60, (int) config('session.lifetime'));
    }
}
