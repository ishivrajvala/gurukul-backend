# Avdhara backend — runbook

Everything here is something that has already gone wrong once, or would have.
Nothing in it should have to be remembered.

---

## The two repositories

| | Where | What |
|---|---|---|
| **Backend** | `D:/Avdhara/Project/gurukul-backend` | Laravel 12 + Filament 3 + Postgres. The admin panel and the `/v1` API. |
| **Frontend** | `D:/Avdhara/Project/avdhara-app` | Next.js. The public website. Reads this API; falls back to its own content when it cannot. |

The frontend finds this API through `NEXT_PUBLIC_API_URL` in its `.env.local`.
See its `ARCHITECTURE.md` §3 for what it reads and writes.

---

## Running it

```bash
php artisan serve                 # the panel at http://127.0.0.1:8000 and the API at /v1
php artisan queue:work            # REQUIRED — see below
npm run theme                     # after any change to the admin stylesheet — see below
```

### `queue:work` is not optional

`QUEUE_CONNECTION=database`, and Filament's notifications implement `ShouldQueue`. Every
submission the site takes raises a notification, and with no worker running they sit in the `jobs`
table for ever: the bell stays empty and nothing anywhere says why.

This is the correct arrangement — an API response should not wait on a notification — but it means
a worker has to be part of running the app, not an afterthought.

**A running worker does not see your code changes.** `queue:work` bootstraps the framework ONCE and
keeps that copy for its whole life — routes, config, classes, all frozen at the moment it started.
A job written against a route added afterwards fails with `Route [...] not defined` while the same
route resolves perfectly in a browser and in `route:list`, which is about as misleading as an error
gets. Run `php artisan queue:restart` after any change a queued job touches; in development, just
stop and start the worker. This has already cost one debugging session on
`Route [subscribers.unsubscribe] not defined`.

### The weekly email

```bash
php artisan subscribers:send-weekly --dry-run   # say what it would do, send nothing
php artisan subscribers:send-weekly             # send to everybody due
php artisan subscribers:send-weekly --force     # ignore both guards, for testing the plumbing
```

Scheduled for **Thursday 09:00** in `routes/console.php`. It goes out over the queue, so
`queue:work` has to be running for anything to actually leave.

Two guards, and neither is redundant:

- **Nothing published this week means no email.** One that arrives regardless teaches people to
  ignore it, and the unsubscribes from an empty send cost you the reader who would have opened the
  next good one.
- **`last_sent_at` per subscriber, stamped at queue time.** The scheduler can fire twice — a
  retried deploy, an overlapping run, somebody running it by hand after a failure — and sending the
  same email twice is the one mistake that cannot be taken back. It also means a run that dies
  halfway resumes rather than restarting.

**Unsubscribing never deletes the row.** The record that somebody asked to stop is the only thing
stopping the next signup or import putting them back on. The link is `/unsubscribe/{token}` with a
random per-subscriber token — never the address or the id, because that link gets forwarded and
quoted, and `?email=` in it would let anybody unsubscribe anybody.

### `npm run theme` is not optional either

The admin theme is a **compiled Filament theme** at `resources/css/filament/admin/theme.css`,
built into `public/css/filament/admin/theme.css`.

`->theme()` **replaces** Filament's own stylesheet rather than adding to it. So if that built file
is missing — a fresh clone, a deploy that skipped the step — the panel links a 404 and renders as
**completely unstyled HTML**, while every page still returns 200. No test that checks status codes
will notice. `tests/Feature/AdminThemeTest.php` exists to catch exactly this.

It is built with the **Tailwind v3 CLI**, not this project's Vite: Filament 3 ships a v3 preset and
`resources/css/app.css` is on Tailwind v4. The two majors cannot share a build. `npx tailwindcss@3`
fetches v3 for the length of the command and leaves nothing behind.

---

## Tests

`phpunit.xml` points at an in-memory SQLite this project has no driver for, so the environment has
to be given on the command line:

```bash
APP_ENV=local DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 \
DB_DATABASE=gurukul_local DB_USERNAME=postgres DB_PASSWORD=gurukul \
php artisan test
```

They run against the **development database on purpose**, without `RefreshDatabase`. The existing
rows are what make them meaningful: an empty table renders even when its columns are broken,
because the closures never run. A test that wiped the data first would pass while the panel was
down.

`AdminPagesRenderTest` derives its page list from the panel rather than a hardcoded array, and
covers list, create **and** edit pages. Edit pages are half the point — a resource's form schema,
header actions and placeholders never run on a list page, and that is where the bugs have been.

---

## Where things are in the panel

| Group | Holds |
|---|---|
| **Inbox** | Circle signups · Circle questions · Story submissions · Leads |
| **Email list** | Subscribers |
| **Content System** | Articles · Landing pages · Seasons |
| **Parenting** | Circles · Gatherings · Stories · Testimonials · Community reviews |
| **Careers** | Open roles · Applications |
| **Taxonomy** | Topics · Age stages · Petals |
| **Settings** | Team · Homepage figures · Homepage concerns |

**Where is the waitlist?** Inbox → Leads → the **Join waitlist** tab. Four forms share one table
(waitlist, contact, book a call, parent guide) because four near-identical screens is four places
to forget to look. Each tab carries an open count and its own empty state naming the form it comes
from.

**Each kind has its own process, and they do not share a status.** They all used to be
`pending / handled / declined`, which is the only vocabulary four processes could agree on and
describes none of them — a booking that has been scheduled is neither pending nor handled, and
"handled" never said whether anybody turned up. The workflows live in `app/Models/LeadKind.php`
and nowhere else:

| Form | Process |
|---|---|
| Join waitlist | new → invited → joined · declined |
| Contact | new → replying → answered · closed |
| Book a call | new → scheduled → completed · no show · cancelled |
| Parent guide | new → sent · failed |

The **Advance** button on a row moves one step along the happy path only. `declined`, `no show`,
`cancelled` and `failed` are never one click away — marking a family as having declined by accident
is not a mistake anybody finds out about.

**Counts are "open", not "untouched".** Open means per kind: an invited family and a scheduled call
are still ours to chase; a declined one is not. `Lead::scopeOpen` draws that line once, and the
sidebar badge, the tab badges and the dashboard stat all read it.

**Subscribers are NOT leads and are not in the Inbox.** They are their own group, because nobody
handles a subscriber — there is nothing to do about one, ever — and while they shared the enquiries
table every signup sat in the pending count as work that could never be finished. A lead is
answered once and closed; a subscriber is written to every week for years.

**Most asked** and **trending searches** are not in the menu. They are ten questions and six terms,
edited twice a year; they are reached from buttons at the top of the Articles list.

---

## Things that will bite

**Content takes up to an hour to appear on the site.** Every frontend read is ISR at
`revalidate = 3600`, and Next's fetch cache survives a rebuild. `rm -rf .next/cache/fetch-cache`
in the frontend when that matters.

**`php artisan serve` cannot serve a frontend build.** It is single-threaded and boots the
framework per request; `next build` renders across seven workers and asks for every article body at
once. The frontend allows 15s and retries once for this reason. A build logging
`[api] ... using local content` has quietly shipped fallback content — and for a page that exists
only in the CMS, that is a 404 from a build that reported success. Behind php-fpm it does not arise.

**A role is what grants panel access.** `User::canAccessPanel` admits anybody holding any role, so
a team member with none cannot sign in at all. It previously named three roles from memory, two of
which did not exist in this database, and the one real non-admin role was refused with a flat 403.

**Postgres and `notifications.data`.** `php artisan notifications:table` scaffolds it as `text`.
Postgres has no `->>` operator for text, and Filament queries `data->>'format'`, so the bell threw
on every page in the panel. Both the scaffolded migration and a corrective one are in place; if you
ever regenerate that migration, make the column `json`.

---

## Locked design rules

These are the website's and they apply to the panel too. Breaking them produces unreadable text.

- **Indigo is the only text or mark on marigold.** White on marigold is ~1.9:1; orange on it ~1.3:1.
  This is why the active sidebar row — a marigold pill — carries indigo text, and why its count
  badge inverts to indigo-on-marigold.
- **Marigold text never on white** (~1.9:1). Marigold is a fill and a mark, never running text.
- **Six brand colours**, from the frontend's `models/tokens.ts`. A shade that does not exist there
  is an opacity of one that does. Colour ramps come from `->colors()` in `AdminPanelProvider`, not
  from the stylesheet.
- **Four colours carry the panel, and each has one job.** Indigo is the primary — headings, the
  sidebar, the primary button, the first cell of every table row. Marigold is the accent and the
  second series on a chart. Green means growth and nothing else. **Orange is the overline colour**:
  table column headers, sidebar group labels, stat labels, and no other text anywhere.
- **Baloo 2 is the panel's face — everywhere.** Headings, labels, table cells, buttons, numbers,
  navigation, badges. **Nunito Sans keeps exactly one job**: description and helper text, the
  sentences somebody reads rather than scans.

  This reversed an earlier split and the reversal is deliberate. Baloo is a rounded display face
  and it *is* mushier than Nunito at 13px — which is why **nothing in this panel is 13px any
  more**. The face and the TEXT SIZE block in `theme.css` are one decision; do not re-split the
  fonts without moving the sizes back too.

**Orange overlines fail WCAG AA and that is a known, accepted exception.** Brand orange measures
2.94:1 on white against the 4.5:1 wanted at that size. It is confined to words that label a region
already obvious from its position — never to anything carrying information — and nothing a person
has to *read* is orange. `--av-orange-ink` (#b34618) is the same hue darkened to 4.54:1 and is
sitting in `:root` unused; swapping the two declarations in the overline block is the whole fix if
that call is ever revisited.
- **Danger stays red.** Brand orange is close enough to be tempting and is the wrong call: people
  expect red for destructive actions, and a delete button in Avdhara orange gets pressed by accident.
- **No comment, view, like, enrolment or rating counts anywhere.** A homepage stat block reading
  "4.9 / 5" and "3,500+ families" was removed for this reason. The one number the site does show is
  how many seats a job opening has, which is a fact a candidate uses rather than social proof.
