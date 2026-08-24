<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
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

    /*
     * THE SIGN-IN FORM ACTUALLY SIGNS SOMEBODY IN.
     *
     * `test_the_login_screen_renders` above cannot catch this and never could: the login page is
     * now a custom Livewire component with a hand-written view (`App\Filament\Admin\Auth\Login`),
     * and a view that dropped `{{ $this->form }}` or the submit action would still return 200 with
     * a headline, a logo and no way in. Every other test in this file reaches the panel through
     * `actingAs`, which walks straight past the form. This is the only one that goes through it.
     *
     * It drives the component rather than posting to a URL because there is no URL to post to —
     * Filament authenticates over Livewire, so `authenticate()` is the endpoint.
     */
    public function test_the_login_form_authenticates_a_real_user(): void
    {
        $password = 'a-long-enough-password';

        $user = User::create([
            'name' => 'Sign In Test',
            'email' => 'sign-in-test@example.invalid',
            'password' => bcrypt($password),
        ]);

        /* A role, because `canAccessPanel` admits nobody without one — see the class docblock. */
        $user->assignRole('content_strategist');

        try {
            Livewire::test(Login::class)
                ->set('data.email', $user->email)
                ->set('data.password', $password)
                ->call('authenticate')
                ->assertHasNoFormErrors();

            $this->assertTrue(
                Filament::auth()->check(),
                'the form submitted without error but nobody was signed in',
            );
        } finally {
            Filament::auth()->logout();
            $user->delete();
        }
    }

    /*
     * And it refuses a wrong password rather than letting one through quietly. The failure arrives
     * as a validation error on the EMAIL field, which is where Filament puts it deliberately: one
     * message under one field cannot tell somebody which of the two they got wrong.
     */
    public function test_the_login_form_refuses_a_wrong_password(): void
    {
        $user = User::create([
            'name' => 'Wrong Password Test',
            'email' => 'wrong-password-test@example.invalid',
            'password' => bcrypt('the-real-password'),
        ]);

        $user->assignRole('content_strategist');

        try {
            Livewire::test(Login::class)
                ->set('data.email', $user->email)
                ->set('data.password', 'not-the-real-password')
                ->call('authenticate')
                ->assertHasFormErrors(['email']);

            $this->assertFalse(Filament::auth()->check());
        } finally {
            $user->delete();
        }
    }

    public function test_a_user_with_a_role_can_reach_the_panel(): void
    {
        $user = $this->adminUser();

        $this->assertNotNull($user, 'no user to act as — seed one first');
        $this->assertTrue(
            $user->roles()->exists(),
            'the seeded user has no role, so nobody can reach the panel at all',
        );

        $this->signedIn($user)->get('/')->assertStatus(200);
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
