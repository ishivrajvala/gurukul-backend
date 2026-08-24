<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //

    /**
     * A signed-in panel session that has already passed two-factor.
     *
     * TWO-FACTOR IS MANDATORY FOR EVERY ACCOUNT (see `RequireTwoFactor`), so `actingAs` alone no
     * longer reaches any panel page — it lands on the enrolment screen, exactly as a real person
     * would. Marking the session verified is the test equivalent of having typed a code.
     *
     * It does NOT enrol the user, deliberately: writing a real TOTP secret onto the development
     * accounts would lock the actual team out of their own dev panel, since nobody has the phone
     * that secret belongs to. `TwoFactorTest` covers the enrolment gate itself.
     */
    protected function signedIn(\App\Models\User $user): static
    {
        return $this->actingAs($user)
            ->withSession([\App\Http\Middleware\RequireTwoFactor::VERIFIED => true]);
    }
}
