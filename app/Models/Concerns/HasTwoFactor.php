<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-factor authentication for a panel account.
 *
 * TOTP, the six-digit code an authenticator app produces. Not SMS: a text message can be
 * intercepted by anybody who can persuade a phone company to move a number, and that is a much
 * easier attack than it sounds. TOTP needs the device itself.
 *
 * EVERYTHING SENSITIVE IS ENCRYPTED, NOT HASHED — except the recovery codes, which are hashed. The
 * distinction is the point: a TOTP secret has to be readable to verify a code against it, so it is
 * encrypted with the app key. A recovery code only ever needs to be COMPARED, so it is hashed and
 * cannot be read back out of the database at all, even by us.
 */
trait HasTwoFactor
{
    public function twoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    /**
     * Start enrolment: a fresh secret, not yet confirmed.
     *
     * DELIBERATELY DOES NOT CONFIRM. A secret that exists is not proof anybody scanned it —
     * confirmation happens only once the person types a code their own app produced. Without that
     * split, an interrupted enrolment would lock somebody out of an account they never finished
     * setting up.
     */
    public function startTwoFactorEnrolment(): string
    {
        $secret = (new Google2FA())->generateSecretKey();

        $this->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    public function twoFactorSecret(): ?string
    {
        if (blank($this->two_factor_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->two_factor_secret);
        } catch (\Throwable) {
            /*
             * A secret that will not decrypt means APP_KEY has changed since it was written. It is
             * unrecoverable rather than wrong — treated as "not enrolled" so the person is walked
             * through enrolment again instead of meeting an error they cannot act on.
             */
            return null;
        }
    }

    /** Is this the code the person's app is showing right now? */
    public function verifyTwoFactorCode(string $code): bool
    {
        $secret = $this->twoFactorSecret();

        if ($secret === null) {
            return false;
        }

        /*
         * A ONE-WINDOW TOLERANCE (±30s). Phone clocks drift, and somebody typing a code as it
         * rolls over should not be told they are wrong. Wider than this starts extending how long
         * an intercepted code stays usable.
         */
        return (new Google2FA())->verifyKey($secret, $code, 1);
    }

    /**
     * Eight single-use recovery codes, hashed, returned in plain ONCE.
     *
     * This is the only moment they are readable. Storing them recoverable would make them a second
     * copy of the password; hashing means a database dump yields nothing usable, and it also means
     * we genuinely cannot tell somebody what their codes were — which is the correct answer.
     */
    public function generateRecoveryCodes(): array
    {
        $plain = collect(range(1, 8))
            ->map(fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();

        $this->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(
                json_encode(array_map(fn (string $c): string => Hash::make($c), $plain), JSON_THROW_ON_ERROR),
            ),
        ])->save();

        return $plain;
    }

    /**
     * Spend a recovery code. Single use — a matched code is removed, not just accepted.
     *
     * Without the removal a recovery code is a permanent second password, and the one written on a
     * sticky note stays valid for ever.
     */
    public function useRecoveryCode(string $code): bool
    {
        $hashes = $this->recoveryCodeHashes();

        foreach ($hashes as $index => $hash) {
            if (Hash::check(trim($code), $hash)) {
                unset($hashes[$index]);

                $this->forceFill([
                    'two_factor_recovery_codes' => Crypt::encryptString(
                        json_encode(array_values($hashes), JSON_THROW_ON_ERROR),
                    ),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public function recoveryCodesRemaining(): int
    {
        return count($this->recoveryCodeHashes());
    }

    /** Confirm enrolment. Only ever called after a code has actually verified. */
    public function confirmTwoFactor(): void
    {
        $this->forceFill(['two_factor_confirmed_at' => now()])->save();
    }

    /** Take it all off — used by `php artisan admin:reset-2fa` when somebody loses their phone. */
    public function resetTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /** @return array<int, string> */
    private function recoveryCodeHashes(): array
    {
        if (blank($this->two_factor_recovery_codes)) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($this->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
