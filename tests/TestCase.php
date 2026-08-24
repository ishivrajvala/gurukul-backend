<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * EVERY test gets a migrated schema, not just the two that remembered to ask for it.
     *
     * Only AdminPagesRenderTest and CampaignSendTest used this trait. The other thirty-four
     * assumed a database that already had tables — which was true only because the suite was being
     * pointed at a hand-migrated development database. That made the tests dependent on the state
     * of somebody's laptop: they passed or failed according to whether that database happened to
     * be up to date, and a fresh checkout could not run them at all.
     *
     * Declared here rather than in each file so a new test cannot forget it and inherit that
     * problem again. It runs against gurukul_test (see phpunit.xml), never the development
     * database, because this trait rebuilds the schema.
     */
    use RefreshDatabase;

    /**
     * Roles exist before any test runs.
     *
     * RefreshDatabase migrates but does not seed, and Spatie throws RoleDoesNotExist rather than
     * returning null — so a fresh schema made twenty-one tests fail on a missing role rather than
     * on anything they were actually asserting.
     *
     * RoleSeeder only, not DatabaseSeeder: the content seeders build articles, circles and stories
     * that no test asserts against, and seeding them would make every run slower and every failure
     * harder to read. Roles are infrastructure; content is fixtures, and a test that needs a
     * particular article should create it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /**
     * An account that can reach the panel, created rather than looked up.
     *
     * Four test files opened with `User::first()` and asserted it had a role. That works only on a
     * machine whose development database has been seeded by hand, and it makes the suite assert
     * something about the developer's data instead of about the code — a fresh checkout, or CI,
     * failed on "no user to act as" before reaching a single real assertion.
     *
     * super_admin because these are panel-wide checks (does every page render, does the theme
     * load); a test about what a content_strategist may NOT do should build its own narrower user.
     */
    protected function adminUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create()->assignRole('super_admin');
    }

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
