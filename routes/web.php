<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\EmailEventController;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Route;

/*
 * UNSUBSCRIBE — a GET, no login, no confirmation step.
 *
 * A one-click link, because the alternative is a spam complaint. Somebody who wants out and meets a
 * sign-in wall or a three-step form marks the email as junk instead, and that costs the sending
 * domain far more than the unsubscribe would have.
 *
 * THE TOKEN IS THE WHOLE AUTHORISATION, and it is random per subscriber rather than the address or
 * the id — see `Subscriber`. A link built from an email address lets anybody unsubscribe anybody;
 * one built from a sequential id lets somebody walk the list.
 *
 * An unknown token still renders the same page. Saying "no such subscriber" would turn this into a
 * way to check whether a given token — and so a given person — is on the list.
 */
Route::get('/unsubscribe/{token}', function (string $token) {
    Subscriber::where('unsubscribe_token', $token)->first()?->unsubscribe();

    return response()->view('subscribers.unsubscribed');
})->middleware('throttle:20,1')->name('subscribers.unsubscribe');

/*
 * BOUNCES AND SPAM COMPLAINTS, posted by the mail provider.
 *
 * A bounce cannot be detected while sending — SMTP says 250 OK and only afterwards discovers the
 * mailbox does not exist — so it arrives here out of band, minutes or hours later. Without this
 * wired up, dead addresses stay marked `sent` for ever and the list rots silently.
 *
 * NOT IN `api.php`, because that group is the public read/write API the website uses and carries
 * its CORS and throttling. This is a machine-to-machine callback authorised by a shared secret in
 * `X-Webhook-Secret`; it is excluded from CSRF in `bootstrap/app.php`.
 */
Route::post('/webhooks/email', EmailEventController::class)
    /*
     * THROTTLED EVEN THOUGH IT IS SECRET-GUARDED. The secret stops somebody writing bounce records;
     * it does not stop them hammering the endpoint, and every request costs a database lookup
     * before the header is even compared. 300 a minute is far above any real provider's callback
     * rate — SES and Postmark batch — and far below what makes a useful denial of service.
     */
    ->middleware('throttle:300,1')
    ->name('webhooks.email');

/*
 * The unsubscribe link is throttled too. It is a GET anybody can call with a guessed token, and
 * without a limit it is an oracle: fire tokens at it until one stops returning the generic page.
 * The page is deliberately identical either way, and this makes guessing slow as well as useless.
 */
