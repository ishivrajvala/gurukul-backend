<?php

declare(strict_types=1);

/**
 * Cross-origin access for the website.
 *
 * THE SITE AND THE API ARE DIFFERENT ORIGINS, and they always will be: the frontend is a Next.js
 * app and this is a Laravel API. Server-rendered reads never touch this — those happen in Node, not
 * a browser — which is exactly why the reads worked from day one and hid the problem. A form POST
 * comes from the browser, and without these headers it fails in the console with the request never
 * reaching PHP.
 *
 * `paths` HAS TO INCLUDE `v1/*`. Laravel's default is `api/*`, and this project sets `apiPrefix:
 * ''` in bootstrap/app.php so the routes live at `/v1/...`. That mismatch is silent: the preflight
 * returns 200 with no `Access-Control-Allow-Origin`, which reads like a working endpoint until you
 * look at the response headers.
 *
 * `allowed_origins` IS A LIST, NOT `*`. These endpoints accept a WhatsApp number, a child's age and
 * a consent record; a wildcard would let any page on the internet submit to them from a visitor's
 * browser. The list is env-driven so production adds its own domain without a code change.
 *
 * `supports_credentials` stays FALSE. Nothing here is session-authenticated — the writes are public
 * and rate limited — and turning it on would forbid the origin list from ever containing `*` while
 * buying nothing.
 */
$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000')),
)));

return [
    'paths' => ['v1/*', 'api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    /* Ten minutes. Long enough that a form does not preflight on every keystroke-triggered submit,
       short enough that changing the rules above takes effect the same morning. */
    'max_age' => 600,

    'supports_credentials' => false,
];
