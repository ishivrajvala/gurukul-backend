<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * One email to one person, and the record of what happened to it.
 *
 * ONE JOB PER RECIPIENT, not one job that loops the list. A loop means one slow handshake stalls
 * everybody behind it, one rejected address can abort the rest, and a worker that dies takes the
 * whole send with it — with no way to tell who had already been written to. Per-recipient jobs
 * retry individually and the delivery row says exactly where each one got to.
 *
 * IT CATCHES ITS OWN FAILURES ON PURPOSE. A throw would let the queue retry, but the reason the
 * server gave would only ever reach `failed_jobs` — a table nobody looks at, keyed by nothing
 * useful. Recording it against `campaign_recipients` is what makes the failure visible on the
 * campaign's own screen, next to the address it belongs to.
 */
class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** Back off between attempts: a full mailbox or a rate limit clears with time, not with speed. */
    public array $backoff = [30, 120];

    public function __construct(public CampaignRecipient $recipient) {}

    public function handle(): void
    {
        $recipient = $this->recipient->fresh();

        /*
         * ALREADY SENT MEANS STOP. The unique index stops a second row being created for the same
         * person; this stops a re-queued job writing to one who already has it.
         */
        if ($recipient === null || $recipient->status === CampaignRecipient::STATUS_SENT) {
            return;
        }

        $subscriber = $recipient->subscriber;
        $campaign = $recipient->campaign;

        if ($subscriber === null || $campaign === null) {
            return;
        }

        /*
         * CHECKED AGAIN HERE, not only when the list was assembled. A long send can take a while,
         * and somebody who unsubscribes while it is running must not get the email that is still in
         * the queue behind them. This is the last point at which that can be honoured.
         */
        if (! $subscriber->isDeliverable()) {
            $recipient->forceFill([
                'status' => CampaignRecipient::STATUS_FAILED,
                'error' => 'Unsubscribed or bounced before this send reached them.',
            ])->save();

            return;
        }

        try {
            Mail::to($subscriber->email)->send(new CampaignMail($campaign, $subscriber));

            $recipient->forceFill([
                'status' => CampaignRecipient::STATUS_SENT,
                'sent_at' => now(),
                'error' => null,
            ])->save();

            $subscriber->forceFill(['last_sent_at' => now()])->save();
        } catch (\Throwable $e) {
            $recipient->forceFill([
                'status' => CampaignRecipient::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            /*
             * A SYNCHRONOUS REJECTION IS NOT A BOUNCE, and the subscriber is deliberately left
             * alone here. The server refusing the connection, a timeout or a rate limit are all
             * ours; marking somebody's address dead because our SMTP had a bad afternoon would
             * quietly shrink the list for a reason that has nothing to do with them. Only a
             * provider's bounce webhook may set `bounced` — see `Subscriber::markBounced`.
             */
            report($e);
        } finally {
            $campaign->refreshCounts();
        }
    }
}
