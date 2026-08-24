{{--
    THE EMAIL SHELL — the brand around whatever was written in the editor.

    TABLES AND INLINE STYLES, ON PURPOSE, and it is not 2005 nostalgia. Outlook renders with Word's
    HTML engine: no flexbox, no grid, no `<style>` blocks it respects reliably, and float support
    that gives up on the second column. A table with inline styles is the only layout every client
    agrees on, and an email is the one thing that cannot be fixed after it is opened.

    THE SAME SHELL RENDERS THE PREVIEW, so what the panel shows is the message that goes out, not an
    approximation of it — `CampaignResource` renders this view into an iframe. A preview built from
    different markup is a preview of nothing.

    Colours are the brand's six from `models/tokens.ts`, written as literal hex because an email has
    no stylesheet and no CSS variables.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f6fa; font-family:'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color:#282828; -webkit-font-smoothing:antialiased;">

{{--
    THE PREHEADER: the grey line a client shows after the subject. Hidden in the body, then padded
    with zero-width spaces so the client cannot drag the first words of the real content up into it
    — which is how "View this in your browser" ends up being the second thing everybody reads.
--}}
@if ($preheader)
    <div style="display:none; font-size:1px; color:#f7f6fa; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $preheader }}
        {!! str_repeat('&#847;&zwnj;&nbsp;', 60) !!}
    </div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f7f6fa;">
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background-color:#ffffff; border-radius:20px; overflow:hidden;">

                {{-- The masthead. Indigo, with the white lockup — the pairing the site's own nav uses. --}}
                <tr>
                    <td align="center" style="background-color:#27156b; padding:28px 24px;">
                        {{--
                            `$logoSrc` IS PASSED IN rather than embedded here, and that is what lets
                            the panel's preview use this same file. `$message->embed()` only exists
                            while a Mailable is rendering; calling it here would make the preview
                            throw. So the mailable passes an embedded CID — which survives a client
                            blocking remote images — and the preview passes a plain URL.
                        --}}
                        <img src="{{ $logoSrc }}"
                             alt="{{ config('app.name') }}"
                             width="150"
                             style="display:block; width:150px; max-width:60%; height:auto; border:0;">
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px 32px 8px 32px;">
                        <h1 style="margin:0 0 20px 0; font-family:'Baloo 2', 'Nunito Sans', Helvetica, Arial, sans-serif; font-size:26px; line-height:1.2; font-weight:700; color:#27156b;">
                            {{ $subject }}
                        </h1>

                        @if ($name)
                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.65; color:#282828;">
                                Hello {{ $name }},
                            </p>
                        @endif
                    </td>
                </tr>

                {{--
                    THE BODY, straight from the editor and unescaped — it is HTML by design.

                    Filament's RichEditor sanitises what it stores, and only somebody who can already
                    sign in to the panel can write here. That is the boundary this depends on: the
                    same trust that lets an editor publish an article to the public site.
                --}}
                <tr>
                    <td style="padding:0 32px 8px 32px; font-size:16px; line-height:1.65; color:#282828;">
                        {!! $body !!}
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 32px 32px;">
                        <hr style="border:0; border-top:1px solid rgba(39,21,107,0.12); margin:0 0 20px 0;">

                        <p style="margin:0 0 8px 0; font-size:13px; line-height:1.6; color:rgba(40,40,40,0.66);">
                            You are getting this because you subscribed on the Avdhara website.
                        </p>

                        {{--
                            THE UNSUBSCRIBE LINK, in the body as well as in the `List-Unsubscribe`
                            header. Not every client shows the header button, and somebody who wants
                            out and cannot find the way out presses "report spam" instead — which
                            costs the sending domain far more than the unsubscribe ever would.
                        --}}
                        <p style="margin:0; font-size:13px; line-height:1.6; color:rgba(40,40,40,0.66);">
                            <a href="{{ $unsubscribeUrl }}" style="color:#27156b; text-decoration:underline;">Unsubscribe</a>
                            and we will stop.
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
