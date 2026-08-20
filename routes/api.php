<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CircleController;
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
Route::prefix('v1')->group(function (): void {
    /* ---- content out ---- */
    Route::get('/taxonomy', [TaxonomyController::class, 'index']);

    Route::get('/journal', [JournalController::class, 'index']);
    Route::get('/journal/{slug}', [JournalController::class, 'show']);

    Route::get('/circles', [CircleController::class, 'index']);
    Route::get('/circles/{slug}', [CircleController::class, 'show']);
    Route::get('/gatherings', [CircleController::class, 'gatherings']);

    Route::get('/stories', [StoryController::class, 'index']);
    Route::get('/stories/{slug}', [StoryController::class, 'show']);
    Route::get('/testimonials', [StoryController::class, 'testimonials']);
    Route::get('/reviews', [StoryController::class, 'reviews']);

    /* ---- submissions in ---- */
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('/circle-signups', [SubmissionController::class, 'circleSignup']);
        Route::post('/circle-questions', [SubmissionController::class, 'circleQuestion']);
        Route::post('/story-submissions', [SubmissionController::class, 'storySubmission']);
        Route::post('/enquiries', [SubmissionController::class, 'enquiry']);
    });
});
