<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Bus;

/**
 * Turning a campaign into one job per subscriber.
 *
 * A SERVICE RATHER THAN A METHOD ON THE MODEL, because three callers need it and they must all
 * behave identically: the Send now button, the scheduler picking up a due campaign, and a retry of
 * a send that failed halfway. Three copies of this loop is three chances for one of them to skip
 * the claim and send an email twice.
 */
final class CampaignDispatcher
{
    /**
     * @return int the number of people this send is going to
     */
    public static function dispatch(Campaign $campaign): int
    {
        /*
         * THE CLAIM COMES FIRST, before a single row is written. If another process already has
         * this campaign the answer is zero and nothing happens — see `Campaign::markSending`.
         */
        if (! $campaign->markSending()) {
            return 0;
        }

        $recipients = [];

        Subscriber::query()->deliverable()->chunkById(500, function ($chunk) use ($campaign, &$recipients): void {
            foreach ($chunk as $subscriber) {
                /*
                 * `firstOrCreate` ON THE UNIQUE PAIR, so re-running a half-finished send picks up
                 * the people who were missed and silently skips the ones already written to. The
                 * database is what enforces that, not this loop's memory of where it got to.
                 */
                $recipient = CampaignRecipient::firstOrCreate(
                    ['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id],
                    ['email' => $subscriber->email, 'status' => CampaignRecipient::STATUS_QUEUED],
                );

                if ($recipient->status === CampaignRecipient::STATUS_SENT) {
                    continue;
                }

                $recipients[] = new SendCampaignEmail($recipient);
            }
        });

        $campaign->refreshCounts();

        if ($recipients === []) {
            /* Nobody to write to. Not a failure — an empty list, which is a fact rather than a fault. */
            $campaign->forceFill([
                'status' => Campaign::STATUS_SENT,
                'sent_at' => now(),
            ])->save();

            return 0;
        }

        /*
         * A BATCH, so the campaign can be marked `sent` when the LAST email has actually gone
         * rather than when the last one was queued. Without it the panel says "Sent" the instant
         * the button is pressed, which is the moment nothing has been delivered at all.
         */
        Bus::batch($recipients)
            ->name('campaign:'.$campaign->id)
            ->allowFailures()
            ->finally(function () use ($campaign): void {
                $campaign->refresh();
                $campaign->refreshCounts();

                $campaign->forceFill([
                    'status' => Campaign::STATUS_SENT,
                    'sent_at' => $campaign->sent_at ?? now(),
                ])->save();
            })
            ->dispatch();

        return count($recipients);
    }
}
