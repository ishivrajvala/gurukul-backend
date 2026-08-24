<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * One campaign, addressed to one person.
 *
 * NOT QUEUED ITSELF. `SendCampaignEmail` is the queued job, and it wraps `Mail::send()` so that it
 * can record the outcome against `campaign_recipients` — success, or the reason the server gave.
 * Queueing the mailable as well would put the actual send on a second queue hop where nothing is
 * watching the result, and the delivery record would say "queued" for ever.
 *
 * ONE MESSAGE PER PERSON, never a bcc list. The unsubscribe link is per subscriber, and a bcc
 * shares one link between everybody on it — the first person to click it unsubscribes somebody
 * else. It also makes a bounce attributable to an address rather than to a send.
 */
class CampaignMail extends Mailable
{
    public function __construct(
        public Campaign $campaign,
        public Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    /**
     * `List-Unsubscribe`, and it is not optional in 2026.
     *
     * Gmail and Yahoo require a one-click unsubscribe header on bulk mail, and without it they
     * throttle or junk the whole domain. It also gives people the button in the mail client's own
     * chrome — which they press instead of "report spam", and a complaint costs the sending
     * reputation far more than an unsubscribe does.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.campaign',
            with: [
                /*
                 * EMBEDDED, not linked. Most clients block remote images by default, and a masthead
                 * that does not load leaves the email opening on a broken-image icon. A CID
                 * attachment always renders. The panel's preview passes a plain URL instead — see
                 * the note in the view.
                 */
                'logoSrc' => $this->embed(public_path('images/logo-white.webp')),
                'subject' => $this->campaign->subject,
                'preheader' => $this->campaign->preheader,
                'body' => $this->campaign->content,
                'name' => $this->subscriber->name,
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
