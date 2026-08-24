<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\WeeklyDigest;
use App\Models\Article;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * THE WEEKLY SEND — the process that makes subscribers a different thing from leads.
 *
 * A lead is worked through a queue by a person. Nobody works a subscriber; a subscriber is written
 * to on a clock, and this is the clock. Scheduled in `routes/console.php`.
 *
 *   php artisan subscribers:send-weekly            send to everybody due
 *   php artisan subscribers:send-weekly --dry-run  say what it would do, send nothing
 *   php artisan subscribers:send-weekly --force    ignore the once-a-week guard
 */
class SendWeeklyDigest extends Command
{
    protected $signature = 'subscribers:send-weekly {--dry-run} {--force}';

    protected $description = 'Send the weekly Journal digest to every deliverable subscriber';

    public function handle(): int
    {
        $articles = Article::query()->published()->where('published_at', '>=', now()->subWeek())->limit(5)->get();

        /*
         * NOTHING TO SAY, SO NOTHING IS SENT.
         *
         * A weekly email that arrives every week whether or not anything happened teaches people to
         * ignore it, and the unsubscribe rate from empty sends is the expensive kind — you lose the
         * reader who WOULD have opened the next good one. `--force` exists for testing the plumbing.
         */
        if ($articles->isEmpty() && ! $this->option('force')) {
            $this->info('Nothing published in the last week. No email sent.');

            return self::SUCCESS;
        }

        $recipients = Subscriber::query()
            ->deliverable()
            /*
             * THE ONCE-A-WEEK GUARD, and it is what makes this command safe to re-run.
             *
             * The scheduler can fire twice — a retried deploy, an overlapping run, somebody running
             * it by hand after a failure. Without this, the second run sends the same email to
             * everybody who already got it, which is the one mistake that cannot be taken back.
             * `last_sent_at` is stamped per address, so a run that dies halfway resumes rather
             * than restarting.
             */
            ->when(! $this->option('force'), fn ($q) => $q->where(
                fn ($inner) => $inner->whereNull('last_sent_at')->orWhere('last_sent_at', '<', now()->subDays(6)),
            ))
            ->get();

        if ($recipients->isEmpty()) {
            $this->info('Nobody is due an email.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                'Would send %d article(s) to %d subscriber(s).',
                $articles->count(),
                $recipients->count(),
            ));

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($recipients as $subscriber) {
            try {
                Mail::to($subscriber->email)->queue(new WeeklyDigest($subscriber, $articles));

                /*
                 * STAMPED AT QUEUE TIME, not on delivery. This column's job is to stop a re-run
                 * sending twice, and by the time delivery is confirmed the re-run has already
                 * happened. It records "we have committed to writing to this person", which is
                 * the fact the guard above needs.
                 */
                $subscriber->forceFill(['last_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                /*
                 * One bad address must not end the run for everybody behind it in the list.
                 */
                report($e);
                $this->warn('Could not queue for '.$subscriber->email);
            }
        }

        $this->info(sprintf('Queued the weekly digest for %d subscriber(s).', $sent));

        return self::SUCCESS;
    }
}
