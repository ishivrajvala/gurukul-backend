<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * The panel's table conventions, which are set globally and can therefore break globally.
 *
 * `AppServiceProvider::configureTableDefaults` applies these to every table in the application at
 * once. That is the right place for a convention and it is also the risk: one wrong guard there
 * changes twenty-three screens, and nothing throws — a button simply stops being anywhere, or
 * appears somewhere it must not. Neither is visible to a test that only checks status codes, which
 * is what every other sweep in this suite does.
 */
class AdminTableConventionsTest extends TestCase
{
    private function panelUser(): User
    {
        /* Built, not found: see `adminUser` on the base TestCase for why looking one up was wrong. */
        return $this->adminUser();
    }

    /**
     * THE NEW BUTTON MOVED INTO THE TABLE, and it is still there.
     *
     * Asserting it EXISTS is half the test. The create action was deleted out of sixteen list
     * pages' `getHeaderActions()` on the understanding that the global default puts it back in the
     * table; if that default ever stops firing, the button does not move, it vanishes — and a
     * resource nobody can add a record to looks exactly like a resource nobody has used yet.
     */
    public function test_the_create_button_renders_inside_the_table(): void
    {
        $html = $this->signedIn($this->panelUser())->get('/topics')->getContent();

        $this->assertStringContainsString('New topic', $html, 'the create button is not on the page at all');

        $table = strpos($html, 'fi-ta-ctn');
        $button = strpos($html, 'New topic');

        $this->assertNotFalse($table, 'no table container rendered');
        $this->assertGreaterThan(
            $table,
            $button,
            'the create button renders before the table, so it is still in the page header',
        );
    }

    /**
     * AND IT RESPECTS `canCreate()`.
     *
     * Subscribers refuses creation on purpose: everybody on that list put themselves on it, and a
     * form that lets somebody type an address in is how a list stops being one people asked to be
     * on. A global default that added a New button to every table would quietly undo that.
     */
    public function test_a_resource_that_refuses_creation_gets_no_button(): void
    {
        $html = $this->signedIn($this->panelUser())->get('/subscribers')->getContent();

        $this->assertStringNotContainsString('New subscriber', $html);
    }
}
