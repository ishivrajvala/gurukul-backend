<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CareerController;
use App\Http\Controllers\Api\V1\CircleController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LandingPageController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\StoryController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TaxonomyController;
use Illuminate\Support\Facades\Route;

/*
 * The Avdhara API.
 *
 * READS ARE PUBLIC AND UNAUTHENTICATED, because everything they return is already on a public
 * website. There is nothing here worth a token that is not also worth a page.
 *
 * WRITES ARE RATE LIMITED, and by IP rather than by session: these forms are open to anybody, and
 * the only thing standing between a submissions table and a spam bot is this line. The limits are
 * deliberately different — a parent submits one story or one reservation in a sitting, so 5/minute
 * is generous; the newsletter and contact forms are the ones a script finds first.
 *
 * REPLACED: /home, /blogs and /landing, which were shaped for an earlier frontend and consumed by
 * nothing. The site is hand-coded and reads none of this yet; `ARCHITECTURE.md` names the three
 * seams where it will.
 */
/*
 * A CEILING ON THE WHOLE PUBLIC API, over and above the tighter limit the writes already carry.
 *
 * Every route below is unauthenticated and cacheable, which is exactly the shape somebody scrapes:
 * the journal, the circles, the taxonomy and every landing page, pulled as fast as the origin will
 * answer. `php artisan serve` and php-fpm both answer until they fall over, and the first sign is
 * the site being slow for the parents actually reading it.
 *
 * 120 A MINUTE PER IP is generous by design — a page render fans out to several of these, and a
 * family behind one office or campus NAT shares an address. It is a ceiling against automation,
 * not a budget for browsing.
 *
 * The POST group inside keeps its own `throttle:6,1`; the tighter limit wins where they overlap.
 */
Route::middleware('throttle:120,1')->prefix('v1')->group(function (): void {
    /* ---- content out ---- */
    Route::get('/taxonomy', [TaxonomyController::class, 'index']);

    /* The homepage's editable bands: the three figures and the three parent concerns. */
    Route::get('/home', [HomeController::class, 'index']);

    /* The top strip. Its own route because it is in the site's root layout, on every page. */
    Route::get('/announcement', [HomeController::class, 'announcement']);

    /* Public profiles. Site-wide chrome like the strip: the footer and the contact page both use it. */
    Route::get('/social-links', [HomeController::class, 'socialLinks']);

    Route::get('/journal', [JournalController::class, 'index']);
    /* Its own path rather than `/journal/meta`, which one article slug away would shadow. */
    Route::get('/journal-meta', [JournalController::class, 'meta']);
    Route::get('/journal/{slug}', [JournalController::class, 'show']);

    Route::get('/circles', [CircleController::class, 'index']);
    Route::get('/circles/{slug}', [CircleController::class, 'show']);
    Route::get('/gatherings', [CircleController::class, 'gatherings']);

    Route::get('/stories', [StoryController::class, 'index']);
    /* The homepage band: chosen and ordered by an editor, lead first. */
    Route::get('/stories-home', [StoryController::class, 'home']);
    Route::get('/stories/{slug}', [StoryController::class, 'show']);
    Route::get('/careers', [CareerController::class, 'index']);

    /* Landing pages. The list carries slugs only — sections come with the page itself. */
    Route::get('/landing', [LandingPageController::class, 'index']);
    Route::get('/landing/{slug}', [LandingPageController::class, 'show']);

    Route::get('/testimonials', [StoryController::class, 'testimonials']);
    Route::get('/reviews', [StoryController::class, 'reviews']);

    /* ---- submissions in ---- */
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('/circle-signups', [SubmissionController::class, 'circleSignup']);
        Route::post('/circle-questions', [SubmissionController::class, 'circleQuestion']);
        Route::post('/story-submissions', [SubmissionController::class, 'storySubmission']);
        /*
         * LEADS AND SUBSCRIBERS ARE TWO ENDPOINTS, because they are two different things: a lead
         * is somebody waiting on a reply, a subscriber is somebody on a weekly list. They shared
         * `/enquiries` while they shared a table.
         */
        Route::post('/leads', [SubmissionController::class, 'lead']);
        Route::post('/subscribers', [SubmissionController::class, 'subscribe']);

        /*
         * THE OLD NAME, KEPT ALIVE ON PURPOSE. A frontend build is deployed separately from this
         * one and Next caches aggressively, so for a while after this ships there will be pages in
         * the wild still posting to `/enquiries`. Dropping it would turn those into silent 404s on
         * a contact form — the failure nobody reports because the page said nothing.
         *
         * It no longer accepts `newsletter`: that kind is gone from `LeadKind`, so an old page's
         * subscribe box gets a 422 rather than quietly filing a subscriber as a lead. Remove this
         * route once the site has been redeployed.
         */
        Route::post('/enquiries', [SubmissionController::class, 'lead']);
        /* Its own endpoint rather than an `enquiry` with kind=career: an application carries a
           role, a required CV and a portfolio, which are columns rather than a JSON blob. */
        Route::post('/job-applications', [CareerController::class, 'apply']);
    });
});
