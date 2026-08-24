<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Prove the mail settings before a campaign does.
 *
 *   php artisan mail:test you@example.com
 *
 * IT SENDS THE REAL SHELL, not "hello world". The point is not that SMTP connects — it is that the
 * message arrives, renders, and is not filed as spam, and none of those can be checked with a
 * plain-text line. The masthead, the fonts, the unsubscribe footer and the List-Unsubscribe header
 * are all things that only reveal themselves in an actual client.
 *
 * IT WRITES NOTHING. No subscriber, no campaign, no delivery row — the objects it builds are never
 * saved, so proving the settings cannot leave test rows in the list.
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {email : Where to send it}';

    protected $description = 'Send one real campaign-shaped email, to check the mail settings';

    public function handle(): int
    {
        $to = (string) $this->argument('email');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not an email address.');

            return self::FAILURE;
        }

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log — this will be written to storage/logs and sent nowhere.');
            $this->warn('See the mail block in .env.example to send for real.');
        }

        /* Unsaved, so this cannot leave anything behind. The token only has to exist for the link. */
        $subscriber = new Subscriber([
            'email' => $to,
            'name' => 'Test',
            'status' => Subscriber::STATUS_SUBSCRIBED,
            'unsubscribe_token' => Str::random(64),
        ]);

        $campaign = new Campaign([
            'subject' => 'Test email from '.config('app.name'),
            'preheader' => 'If you can read this, the mail settings work.',
            'content' => '<p>This is a test of the campaign email shell.</p>'
                .'<p>If the logo above rendered, the fonts look right and the unsubscribe link below '
                .'is present, the settings are correct and a real campaign will look like this.</p>',
        ]);

        try {
            Mail::to($to)->send(new CampaignMail($campaign, $subscriber));
        } catch (\Throwable $e) {
            $this->error('Could not send: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent to '.$to.' via "'.config('mail.default').'".');

        return self::SUCCESS;
    }
}
