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
| **Inbox** | Circle signups · Circle questions · Story submissions · Enquiries |
| **Content System** | Articles · Landing pages · Seasons |
| **Parenting** | Circles · Gatherings · Stories · Testimonials · Community reviews |
| **Careers** | Open roles · Applications |
| **Taxonomy** | Topics · Age stages · Petals |
| **Settings** | Team · Homepage figures · Homepage concerns |

**Where is the waitlist?** Inbox → Enquiries → the **Waitlist** tab. Five forms share one table
(waitlist, subscribers, contact, call bookings, parent guide) because five near-identical screens
is five places to forget to look. Each tab carries a pending count and its own empty state naming
the form it comes from.

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
- **Danger stays red.** Brand orange is close enough to be tempting and is the wrong call: people
  expect red for destructive actions, and a delete button in Avdhara orange gets pressed by accident.
- **No comment, view, like, enrolment or rating counts anywhere.** A homepage stat block reading
  "4.9 / 5" and "3,500+ families" was removed for this reason. The one number the site does show is
  how many seats a job opening has, which is a fact a candidate uses rather than social proof.
