<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Resources\JobApplicationResource;
use App\Filament\Admin\Resources\CallRequestResource;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Subscriber;
use App\Models\JobApplication;
use App\Models\JobRole;
use App\Models\StorySubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every admin page renders for a logged-in user — list pages AND edit pages.
 *
 * THIS EXISTS BECAUSE TWO BUGS GOT PAST A SMOKE TEST THAT ONLY CHECKED CLASSES RESOLVE. A missing
 * heroicon and a closure argument named `$s` instead of `$state` are both perfectly valid PHP: they
 * throw when the sidebar draws and when a table column draws, and nothing before that point knows.
 *
 * So this asks the only question that matters — does the page come back 200 — through the real HTTP
 * stack, with a real session, the way a person hits it.
 *
 * THE EDIT PAGES ARE HALF THE POINT. A resource's form schema, its header actions and its
 * placeholders never run on the list page, so a list-only sweep was passing over exactly the kind
 * of code that broke last time. Each resource's newest row is opened; a resource with no rows is
 * skipped and NAMED in the output rather than counted as a pass, because an unrendered page is not
 * a working one.
 *
 * It runs against the DEVELOPMENT DATABASE on purpose, without RefreshDatabase. The existing rows
 * are what make it meaningful: an empty table renders even when its columns are broken, because
 * the closures never run. A test that wiped the data first would have passed while the panel was
 * down.
 *
 * RUN IT LIKE THIS — phpunit.xml points at an in-memory SQLite this project has no driver for:
 *
 *   APP_ENV=local DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432
 *   DB_DATABASE=gurukul_local DB_USERNAME=postgres DB_PASSWORD=gurukul
 *   php artisan test --filter=AdminPagesRenderTest
 */
class AdminPagesRenderTest extends TestCase
{
    /**
     * Every list page, DERIVED FROM THE PANEL rather than hand-listed.
     *
     * It was a hardcoded array of thirteen slugs, and the moment two resources were added it was
     * quietly checking thirteen of fifteen pages while still passing. A list of things to test that
     * does not maintain itself goes stale without ever failing.
     */
    public function test_every_admin_page_renders(): void
    {
        $user = $this->panelUser();
        $failures = [];

        $paths = ['/'];

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $paths[] = $resource::getUrl('index');
        }

        $this->assertGreaterThan(10, count($paths), 'the panel reported almost no resources');

        foreach ($paths as $path) {
            $response = $this->signedIn($user)->get($path);

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf('%s → %d', $path, $response->getStatusCode());
            }
        }

        $this->assertSame([], $failures, "Admin pages that did not render:\n".implode("\n", $failures));
    }

    /**
     * Create pages too.
     *
     * They run the same form schema as edit but with NO record, which is the one thing that turns a
     * `fn (Lead $record)` in a placeholder or a default into a 500 that the edit sweep never
     * sees — the argument is null on a create page and typed closures do not accept it.
     */
    public function test_every_create_page_renders(): void
    {
        $user = $this->panelUser();
        $failures = [];

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            if (! array_key_exists('create', $resource::getPages())) {
                continue;
            }

            $url = $resource::getUrl('create');
            $response = $this->signedIn($user)->get($url);

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf('%s → %d', $url, $response->getStatusCode());
            }
        }

        $this->assertSame([], $failures, "Create pages that did not render:\n".implode("\n", $failures));
    }

    public function test_every_edit_page_renders(): void
    {
        $user = $this->panelUser();
        $failures = [];
        $skipped = [];
        /** @var list<Model> $created */
        $created = [];

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            if (! array_key_exists('edit', $resource::getPages())) {
                continue;
            }

            /*
             * THROUGH THE RESOURCE'S OWN QUERY, not the bare model.
             *
             * Two resources now share the `Lead` table and scope it to disjoint halves — Leads
             * excludes `booking`, Call requests is nothing but. Asking the model directly hands back
             * whichever row is newest and then 404s, because the resource cannot see it. That is
             * not a fixture problem: it is the same mistake a real scoped resource would make, and
             * the query is the only place that knows what each screen is allowed to show.
             */
            /** @var Model|null $record */
            $record = $resource::getEloquentQuery()->latest('id')->first();

            if ($record === null) {
                /*
                 * The inbox tables are empty until somebody uses the site, so their edit pages —
                 * which is where the placeholders and the download action live — were covered only
                 * by accident, whenever leftover test data happened to be sitting in the dev
                 * database. The test provides its own row and removes it again.
                 */
                $record = $this->throwawayRecordFor($resource);

                if ($record === null) {
                    $skipped[] = $resource::getSlug();

                    continue;
                }

                $created[] = $record;
            }

            $url = $resource::getUrl('edit', ['record' => $record]);
            $response = $this->signedIn($user)->get($url);

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf('%s → %d', $url, $response->getStatusCode());
            }
        }

        /* Before the assertion, so a failing page does not leave rows behind in the dev database. */
        foreach ($created as $record) {
            if ($record instanceof JobApplication && $record->cv_path) {
                Storage::disk('local')->delete($record->cv_path);
            }

            $record->delete();
        }

        $this->assertSame([], $failures, "Edit pages that did not render:\n".implode("\n", $failures));

        /* Not a failure — but a resource nobody has a row for is a page nobody has rendered, and
           that belongs in the output rather than behind a green tick. */
        if ($skipped !== []) {
            fwrite(STDERR, "\n  no rows, so not rendered: ".implode(', ', $skipped)."\n");
        }
    }

    /**
     * A minimal, disposable row for a table the SITE fills in rather than an editor.
     *
     * Only the inbox models. Everything else is content somebody has seeded, and inventing a fake
     * article to render an edit page would be testing the fixture rather than the panel.
     *
     * This exists because those pages were covered only by accident: they rendered whenever
     * leftover data happened to be sitting in the dev database, and stopped the moment it was
     * cleaned up. Coverage that depends on somebody having used the site is not coverage.
     *
     * Returns null for anything not listed, which is what puts a resource in the skipped line.
     */
    private function throwawayRecordFor(string $resource): ?Model
    {
        /*
         * KEYED BY RESOURCE FIRST, because `Lead` backs two of them and a row that satisfies one
         * is invisible to the other. Everything else is a one-model-one-resource table and matches
         * on the model as it always did.
         */
        if ($resource === CallRequestResource::class) {
            return Lead::create([
                'kind' => 'booking',
                'name' => 'Render test',
                'email' => 'render-test@example.invalid',
                'message' => 'A row that exists for the length of this test.',
                'status' => 'new',
            ]);
        }

        return match ($resource::getModel()) {
            /*
             * A CAMPAIGN, because its edit page mounts a RELATION MANAGER and nothing else in this
             * sweep does. A relation manager is a separate Livewire component resolved by class
             * name at mount; with `optimize-autoloader` on, a newly added one is absent from the
             * classmap until `composer dump-autoload` runs, and the page dies with
             * `Unable to find component`. Every test here passed while that was broken in the
             * browser, because with no Campaign row the edit page was silently skipped.
             */
            Campaign::class => Campaign::create([
                'subject' => 'Render test',
                'content' => '<p>A row that exists for the length of this test.</p>',
                'status' => Campaign::STATUS_DRAFT,
            ]),
            Lead::class => Lead::create([
                'kind' => 'contact',
                'name' => 'Render test',
                'email' => 'render-test@example.invalid',
                'message' => 'A row that exists for the length of this test.',
                'status' => 'new',
            ]),
            /*
             * A subscriber, because its edit page reads `unsubscribe_token` and the status select
             * — neither of which runs on the list page, which is exactly the gap this sweep exists
             * to cover.
             */
            Subscriber::class => Subscriber::subscribe('render-test@example.invalid', 'Render test', 'test'),
            JobApplication::class => $this->throwawayApplication(),
            StorySubmission::class => StorySubmission::create([
                'name' => 'Render test',
                'email' => 'render-test@example.invalid',
                'story' => 'A row that exists for the length of this test.',
                'has_consent' => true,
                'consent_text' => 'Consent recorded verbatim, as the endpoint does.',
                'status' => 'pending',
            ]),
            default => null,
        };
    }

    /**
     * An application WITH a CV, so the edit page's download action and the `visible` closure
     * guarding it both actually run. Without a file, the one branch that matters never executes.
     *
     * Null when no role exists to attach it to, which puts the resource in the skipped line rather
     * than inventing a vacancy.
     */
    private function throwawayApplication(): ?JobApplication
    {
        $role = JobRole::first();

        if ($role === null) {
            return null;
        }

        $path = 'applications/cvs/render-test.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF\n");

        return JobApplication::create([
            'job_role_id' => $role->id,
            'name' => 'Render test',
            'email' => 'render-test@example.invalid',
            'cv_path' => $path,
            'status' => 'pending',
        ]);
    }

    /**
     * The careers CV comes back through the panel, and only through the panel.
     *
     * The file is stored on the `local` disk precisely so that no URL reaches it — a CV carries a
     * home address and a phone number. That makes the download action the only way to read one, so
     * if it breaks, an application is silently unreadable.
     */
    public function test_a_cv_is_on_disk_and_downloads_under_a_readable_name(): void
    {
        /* Its own row rather than whatever happens to be in the inbox: this used to skip on a clean
           database, which is exactly when nobody would notice it had stopped running. */
        $application = JobApplication::whereNotNull('cv_path')->latest('id')->first()
            ?? $this->throwawayApplication();

        if ($application === null) {
            $this->markTestSkipped('no role to attach an application to');
        }

        $disposable = $application->wasRecentlyCreated ? $application : null;

        $this->assertTrue(
            Storage::disk('local')->exists($application->cv_path),
            'the row points at a file that is not on disk',
        );

        $name = JobApplicationResource::cvFilename($application);

        if ($disposable !== null) {
            Storage::disk('local')->delete($disposable->cv_path);
            $disposable->delete();
        }

        /* The stored name is a random hash, which is right on disk and useless in a downloads
           folder once three of them are sitting there. */
        $this->assertStringNotContainsString('/', $name, 'the download name must not be a path');
        $this->assertMatchesRegularExpression('/\.(pdf|doc|docx)$/', $name);
    }

    private function panelUser(): User
    {
        $user = $this->adminUser();

        $this->assertNotNull($user, 'no user to act as — seed one first');
        $this->assertTrue(
            $user->canAccessPanel(Filament::getPanel('admin')),
            'the first user cannot reach the panel — give them a role',
        );

        return $user;
    }
}
