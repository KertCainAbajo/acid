<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Layer 3: binds a signed-in session to the browser that created it and expires it after inactivity.
 */
class SessionGuard
{
    public function __construct(private readonly LoginGuard $loginGuard) {}

    /**
     * Record the browser and activity time for a freshly started session.
     */
    public function stamp(Request $request): void
    {
        $request->session()->put([
            'security.fingerprint' => $this->loginGuard->deviceFingerprint($request),
            'security.last_activity' => now()->getTimestamp(),
        ]);
    }

    /**
     * Why the session must end ('idle' or 'device'), or null when it is still trustworthy.
     */
    public function violation(Request $request, User $user): ?string
    {
        $session = $request->session();
        if (! $session->has('security.fingerprint')) {
            $this->stamp($request);

            return null;
        }

        if (! hash_equals((string) $session->get('security.fingerprint'), $this->loginGuard->deviceFingerprint($request))) {
            return 'device';
        }

        $idleSeconds = now()->getTimestamp() - (int) $session->get('security.last_activity', 0);
        if ($idleSeconds > $user->idleTimeoutMinutes() * 60) {
            return 'idle';
        }

        $session->put('security.last_activity', now()->getTimestamp());

        return null;
    }
}
