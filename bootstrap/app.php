<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * THE MAIL PROVIDER'S WEBHOOK CANNOT CARRY A CSRF TOKEN. It is a machine posting from
         * somebody else's infrastructure with no session and no form behind it, so the token check
         * would reject every bounce report as a 419 — silently, from this application's point of
         * view, since a provider retries and then gives up.
         *
         * It is authorised instead by a shared secret in `X-Webhook-Secret`, checked in
         * `EmailEventController` before anything is read from the payload.
         */
        $middleware->validateCsrfTokens(except: [
            'webhooks/email',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
