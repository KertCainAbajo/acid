<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Time-based one-time passwords (RFC 6238) compatible with Google Authenticator, Microsoft Authenticator, Authy and similar apps.
 */
class TwoFactorService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    /** Accept codes from one period before or after, to allow for clock drift. */
    private const WINDOW = 1;

    public function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $chunk): string => self::ALPHABET[bindec(str_pad($chunk, 5, '0'))], str_split($bits, 5)));
    }

    /**
     * The otpauth:// link authenticator apps understand. Opening it on a phone adds the account directly.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'RefluxCare'));

        return "otpauth://totp/{$issuer}:".rawurlencode($user->email)."?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=".self::DIGITS.'&period='.self::PERIOD;
    }

    public function codeAt(string $secret, int $timestep): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $timestep), $this->decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Check a code from an authenticator app. Each code can only be used once, so an intercepted code cannot be replayed.
     */
    public function verify(string $secret, string $code, ?string $replayKey = null): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $current = intdiv(now()->getTimestamp(), self::PERIOD);
        for ($step = $current - self::WINDOW; $step <= $current + self::WINDOW; $step++) {
            if (hash_equals($this->codeAt($secret, $step), $code)) {
                if ($replayKey !== null) {
                    $lastUsed = (int) Cache::get("two-factor-used:{$replayKey}", 0);
                    if ($step <= $lastUsed) {
                        return false;
                    }
                    Cache::put("two-factor-used:{$replayKey}", $step, now()->addMinutes(5));
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))->map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)))->all();
    }

    /**
     * Use (and burn) a recovery code. Returns false if it is not one of the user's unused codes.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::upper(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($code, $codes, true);
        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }

    private function decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret))) as $character) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $character)), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $byte): string => chr(bindec($byte)), array_filter(str_split($bits, 8), fn (string $byte): bool => strlen($byte) === 8)));
    }
}
