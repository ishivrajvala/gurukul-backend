<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\RequireTwoFactor;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Two-factor, which is mandatory for every account here.
 *
 * The lock-out cases matter as much as the security ones. A mandatory second factor with no way
 * back in is a feature somebody switches off in a hurry the first time a phone breaks, so the
 * recovery path is tested as carefully as the happy one.
 */
class TwoFactorTest extends TestCase
{
    private ?User $user = null;

    protected function tearDown(): void
    {
        $this->user?->forceDelete();

        parent::tearDown();
    }

    private function enrolledUser(): User
    {
        $this->user = User::create([
            'name' => 'Two Factor Test',
            'email' => 'two-factor-test@example.invalid',
            'password' => bcrypt('a-long-enough-password'),
        ]);
        $this->user->assignRole('content_strategist');

        return $this->user;
    }

    /** A correct code from the shared secret verifies; a wrong one does not. */
    public function test_a_valid_totp_code_verifies(): void
    {
        $user = $this->enrolledUser();
        $secret = $user->startTwoFactorEnrolment();

        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->assertTrue($user->verifyTwoFactorCode($code));
        $this->assertFalse($user->verifyTwoFactorCode('000000'));
    }

    /**
     * A SECRET ALONE IS NOT ENROLMENT. Scanning a QR proves nothing — the phone may not have saved
     * it. Only a confirmed code counts, or an interrupted setup would lock somebody out.
     */
    public function test_a_secret_without_confirmation_is_not_enrolled(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();

        $this->assertFalse($user->twoFactorEnabled(), 'an unconfirmed secret must not count as enrolled');

        $user->confirmTwoFactor();

        $this->assertTrue($user->fresh()->twoFactorEnabled());
    }

    /** Recovery codes work once each, and spending one removes it. */
    public function test_a_recovery_code_works_exactly_once(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();
        $codes = $user->generateRecoveryCodes();

        $this->assertCount(8, $codes);
        $this->assertSame(8, $user->recoveryCodesRemaining());

        $this->assertTrue($user->useRecoveryCode($codes[0]), 'a fresh recovery code should work');
        $this->assertSame(7, $user->recoveryCodesRemaining(), 'a used code must be removed');
        $this->assertFalse($user->useRecoveryCode($codes[0]), 'a used code must not work twice');
    }

    /** Recovery codes are hashed — a database dump yields nothing usable. */
    public function test_recovery_codes_are_not_stored_readably(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();
        $codes = $user->generateRecoveryCodes();

        $stored = (string) $user->fresh()->two_factor_recovery_codes;

        $this->assertStringNotContainsString($codes[0], $stored, 'a recovery code is readable in the database');
    }

    /** The secret is never exposed by serialising the user. */
    public function test_the_secret_is_hidden_from_serialisation(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();

        $array = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
    }

    /**
     * THE GATE ITSELF: an authenticated user without a second factor cannot reach the panel and is
     * sent to set one up. This is what makes it mandatory rather than optional.
     */
    public function test_an_unenrolled_user_is_forced_to_setup(): void
    {
        $user = $this->enrolledUser();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(\App\Filament\Admin\Auth\TwoFactorSetup::getUrl());
    }

    /** Enrolled but not yet challenged this session: sent to the challenge, not to the panel. */
    public function test_an_enrolled_user_must_pass_the_challenge(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(\App\Filament\Admin\Auth\TwoFactorChallenge::getUrl());
    }

    /** Once the session is verified, the panel opens normally. */
    public function test_a_verified_session_reaches_the_panel(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor();

        $this->actingAs($user)
            ->withSession([RequireTwoFactor::VERIFIED => true])
            ->get('/')
            ->assertStatus(200);
    }

    /** The escape hatch returns an account to unenrolled so it can start again. */
    public function test_resetting_clears_everything(): void
    {
        $user = $this->enrolledUser();
        $user->startTwoFactorEnrolment();
        $user->confirmTwoFactor();
        $user->generateRecoveryCodes();

        $user->resetTwoFactor();

        $this->assertFalse($user->fresh()->twoFactorEnabled());
        $this->assertSame(0, $user->fresh()->recoveryCodesRemaining());
    }
}
