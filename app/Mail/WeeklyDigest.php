<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The weekly email: what went up on the Journal this week.
 *
 * QUEUED, like everything else that leaves this application. A weekly send walks the whole list one
 * address at a time; doing that inside the scheduler's process means one slow SMTP handshake stalls
 * the run, and a run that dies halfway has already sent to some people and cannot tell you which.
 * On the queue each address is its own job with its own retry. `queue:work` is not optional — see
 * RUNBOOK.md.
 *
 * ONE MAILABLE PER SUBSCRIBER, not one with everybody bcc'd. The unsubscribe link is per person,
 * and a bcc list shares one link between everybody on it: the first person to click it
 * unsubscribes somebody else. It also makes a bounce attributable to an address rather than to a
 * send.
 */
class WeeklyDigest extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Subscriber $subscriber,
        public Collection $articles,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'This week from Avdhara',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.weekly-digest',
            with: [
                'name' => $this->subscriber->name,
                'articles' => $this->articles,
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
