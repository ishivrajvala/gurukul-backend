<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Who can reach the panel, and who cannot.
 *
 * THIS REPLACES LARAVEL'S STOCK `ExampleTest`, which asserted that `/` returns 200. That was true
 * of the framework's welcome page and has not been true since: `/` is the admin panel, and a guest
 * hitting it is redirected to the login screen. The test failed for the right reason and was
 * testing nothing anybody cared about, so it is now testing the thing that actually matters at
 * that URL.
 *
 * A ROLE IS WHAT GRANTS ACCESS. `canAccessPanel` used to name three roles from memory — two of
 * which did not exist in this database — so the one real non-admin role was refused with a flat
 * 403 and nothing explaining it. That is the failure this pins down.
 */
class PanelAccessTest extends TestCase
{
    public function test_a_guest_is_sent_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_a_user_with_a_role_can_reach_the_panel(): void
    {
        $user = User::first();

        $this->assertNotNull($user, 'no user to act as — seed one first');
        $this->assertTrue(
            $user->roles()->exists(),
            'the seeded user has no role, so nobody can reach the panel at all',
        );

        $this->actingAs($user)->get('/')->assertStatus(200);
    }

    public function test_a_user_with_no_role_is_refused(): void
    {
        /*
         * Created and removed in the same test rather than seeded: this asserts a REFUSAL, and a
         * roleless account left lying in the development database is exactly the sort of thing
         * somebody later grants a role to without knowing why it existed.
         */
        $stranger = User::create([
            'name' => 'No Role',
            'email' => 'no-role@example.invalid',
            'password' => bcrypt('a-long-enough-password'),
        ]);

        try {
            $this->actingAs($stranger)->get('/')->assertForbidden();
        } finally {
            $stranger->delete();
        }
    }
}
