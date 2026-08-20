<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Every admin page renders for a logged-in user.
 *
 * THIS EXISTS BECAUSE TWO BUGS GOT PAST A SMOKE TEST THAT ONLY CHECKED CLASSES RESOLVE. A missing
 * heroicon and a closure argument named `$s` instead of `$state` are both perfectly valid PHP: they
 * throw when the sidebar draws and when a table column draws, and nothing before that point knows.
 *
 * So this asks the only question that matters — does the page come back 200 — through the real HTTP
 * stack, with a real session, the way a person hits it.
 *
 * It runs against the DEVELOPMENT DATABASE on purpose, without RefreshDatabase. The seeded rows are
 * what make it meaningful: an empty table renders even when its columns are broken, because the
 * closures never run. A test that wiped the data first would have passed while the panel was down.
 *
 * RUN IT LIKE THIS — phpunit.xml points at an in-memory SQLite this project has no driver for, and
 * `User` does not implement `FilamentUser`, so Filament grants panel access in `local` only:
 *
 *   APP_ENV=local DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432
 *   DB_DATABASE=gurukul_local DB_USERNAME=postgres DB_PASSWORD=gurukul
 *   php artisan test --filter=AdminPagesRenderTest
 *
 * That `FilamentUser` gap is worth fixing before production: as it stands nobody can reach the
 * panel outside local, and the failure is a flat 403 with nothing explaining it.
 */
class AdminPagesRenderTest extends TestCase
{
    /** Every resource's list page, by its Filament route slug. */
    private const PAGES = [
        '/',
        '/topics',
        '/age-stages',
        '/articles',
        '/circles',
        '/gatherings',
        '/stories',
        '/testimonials',
        '/community-reviews',
        '/circle-signups',
        '/circle-questions',
        '/story-submissions',
        '/enquiries',
    ];

    public function test_every_admin_page_renders(): void
    {
        $user = User::first();

        $this->assertNotNull($user, 'no user to act as — seed one first');

        $failures = [];

        foreach (self::PAGES as $path) {
            $response = $this->actingAs($user)->get($path);

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf('%s → %d', $path, $response->getStatusCode());
            }
        }

        $this->assertSame([], $failures, "Admin pages that did not render:\n" . implode("\n", $failures));
    }
}
