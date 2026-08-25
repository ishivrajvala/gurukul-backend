{{--
    THE PARENT GUIDE — a transactional message, not a campaign.

    TABLES AND INLINE STYLES, for the reason `campaign.blade.php` sets out at length: Outlook renders
    with Word's HTML engine, and an email is the one thing that cannot be fixed after it is opened.

    NO UNSUBSCRIBE FOOTER, and that is deliberate rather than an omission. Asking for the guide does
    not put anybody on the newsletter — that is a separate form and a separate consent — so an
    unsubscribe link here would offer to remove somebody from a list they are not on, and imply they
    had been added to it without asking.

    Colours are the brand's, written as literal hex because an email has no stylesheet.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ config('guide.subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f6fa; font-family:'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color:#282828; -webkit-font-smoothing:antialiased;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f7f6fa;">
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; background-color:#ffffff; border-radius:12px; overflow:hidden;">

                <tr>
                    <td style="padding:32px 32px 8px 32px;">
                        <p style="margin:0; font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:#4a3f8c; font-weight:700;">Avdhara</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 32px 0 32px;">
                        <h1 style="margin:0; font-size:24px; line-height:1.3; color:#282828; font-weight:700;">
                            @if ($name)
                                {{ $name }}, your guide is attached
                            @else
                                Your guide is attached
                            @endif
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 32px 0 32px; font-size:16px; line-height:1.6; color:#3f3f46;">
                        <p style="margin:0 0 16px 0;">Thank you for asking for it. The guide is attached to this email as a PDF, so it is yours to keep and to read whenever suits.</p>
                        <p style="margin:0 0 16px 0;">It is written to be useful rather than complete: what actually changes between two and sixteen, what is worth noticing at each stage, and what is safe to stop worrying about.</p>
                        <p style="margin:0;">If you have a question about your own child, you can reply to this email. A person reads it.</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 32px 32px;">
                        <p style="margin:0; font-size:14px; line-height:1.6; color:#71717a;">
                            You are receiving this because you asked for the guide on our website. It does not add you to our newsletter.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
