<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ParentGuideMail;
use App\Models\Lead;
use App\Models\LeadKind;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send the parent guide, and record on the lead whether it actually went.
 *
 * THE REQUEST IS THE WHOLE INTERACTION. Somebody typed an address to get one file; making them wait
 * on a person to notice and forward it is the difference between a working promise and a form that
 * looks broken. So this runs automatically the moment the lead is created — see
 * `SubmissionController` — and the `new → sent` transition on `LeadKind::ParentGuide` finally means
 * something rather than describing a task nobody was doing.
 *
 * IT CATCHES ITS OWN FAILURES, for the reason `SendCampaignEmail` does: a throw would leave the
 * reason in `failed_jobs`, a table nobody looks at, while the lead sat at `new` looking merely
 * unattended. Recording `failed` against the lead puts it on the screen somebody is already
 * watching, next to the address it belongs to, where the one-click resend also lives.
 *
 * THE MISSING FILE IS THE CASE THAT MATTERS TODAY. No guide PDF has been produced yet, so every
 * request will land here and mark itself `failed` with a reason saying exactly that — which is the
 * honest outcome. The alternative, sending an email with nothing attached and calling it `sent`,
 * would tell the parent their guide had arrived and tell the panel the job was done. Drop the file
 * at the path in `config/guide.php` and re-send from the leads screen; nothing else has to change.
 */
class SendParentGuide implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Lead $lead) {}

    public function handle(): void
    {
        /* Guard the kind: a resend action on the wrong row should do nothing rather than post a
           guide to somebody who asked to be called. */
        if ($this->lead->kind !== LeadKind::ParentGuide) {
            return;
        }

        /* Already gone. A re-queued or double-clicked job must not send a second copy. */
        if ($this->lead->status === 'sent') {
            return;
        }

        if (! $this->lead->email) {
            $this->fail('No email address on the request.');

            return;
        }

        if (! ParentGuideMail::fileExists()) {
            $this->fail(
                'The guide file is not on the server yet ('
                .config('guide.disk').':'.config('guide.path').').'
            );

            return;
        }

        try {
            Mail::to($this->lead->email)->send(new ParentGuideMail($this->lead));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->lead->recordContact(
            'emailed',
            'The parent guide was sent automatically.',
            'sent',
            null,
        );
    }

    /**
     * Mark the lead failed and say why, in the note trail rather than only in a log.
     *
     * The reason is the thing somebody needs: "failed" on its own sends them looking through server
     * logs for a bad address that is sitting in front of them.
     */
    private function fail(string $reason): void
    {
        Log::warning('Parent guide not sent', ['lead' => $this->lead->id, 'reason' => $reason]);

        $this->lead->recordContact('note', 'The guide could not be sent. '.$reason, 'failed', null);
    }
}
