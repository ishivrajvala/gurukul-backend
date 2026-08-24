{{--
    The page the unsubscribe link lands on.

    STANDALONE, not a Filament view and not the site's layout. It is reached from an email by
    somebody who is not signed in and may never have opened the panel, and it is the last thing this
    application ever says to them — so it loads nothing, depends on nothing, and cannot break
    because a stylesheet somewhere else was rebuilt.

    THE SAME PAGE FOR AN UNKNOWN TOKEN. Telling somebody "no such subscriber" would turn the link
    into a way to test whether a given address is on the list.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.ico') }}">
    <style>
        /* The brand's six tokens, inline: this page must render with nothing else loaded. */
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #ffffff;
            color: #282828;
            font-family: 'Nunito Sans', ui-sans-serif, system-ui, sans-serif;
            line-height: 1.6;
        }
        .card { max-width: 30rem; text-align: center; }
        img { height: 3.5rem; width: auto; margin-bottom: 2rem; }
        h1 { color: #27156b; font-size: 1.75rem; margin: 0 0 0.75rem; }
        p { margin: 0 0 1rem; color: rgba(40, 40, 40, 0.78); }
        a { color: #27156b; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}">

        <h1>You're unsubscribed</h1>

        <p>You won't get the weekly email from us again. Nothing else changes — if you have written
            to us or joined the waitlist, that is untouched.</p>

        <p><a href="{{ config('app.frontend_url') }}">Back to the website</a></p>
    </div>
</body>
</html>
