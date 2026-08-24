{{--
    The weekly email.

    Markdown mail rather than a hand-built HTML template, because an email that has to survive
    Outlook, Gmail and Apple Mail is a table-layout problem nobody should solve twice. Laravel's
    components are already tested against those clients.

    NO TRACKING PIXEL, NO OPEN COUNTS, NO CLICK COUNTS. The site's locked rules forbid showing view,
    like and rating counts anywhere, and quietly collecting the same numbers off the back of an
    email is the same decision made where nobody can see it.
--}}
@component('mail::message')
# {{ $name ? 'Hello '.$name : 'Hello' }}

Here is what went up on the Journal this week.

@forelse ($articles as $article)
## {{ $article->title }}

{{ $article->short_description ?: $article->standfirst }}

@component('mail::button', ['url' => rtrim((string) config('app.frontend_url'), '/').'/journal/'.$article->slug])
Read it
@endcomponent

@empty
Nothing new went up this week — the next one will be along shortly.
@endforelse

Thanks for reading,<br>
{{ config('app.name') }}

@component('mail::subcopy')
You are getting this because you subscribed on the Avdhara website.
[Unsubscribe]({{ $unsubscribeUrl }}) and we will stop.
@endcomponent
@endcomponent
