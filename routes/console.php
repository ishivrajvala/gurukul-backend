<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('activate:theme')->daily();

/*
 * THE WEEKLY EMAIL. Thursday morning, which is a choice rather than a default — it is the send the
 * whole `Subscriber` model exists to serve.
 *
 * `withoutOverlapping` because the run walks the entire list one address at a time and can outlive
 * its hour on a big list; two copies running at once would both read the same `last_sent_at` and
 * both decide to send. The command guards against that per address as well, and neither guard is
 * redundant — this one stops the work, that one stops the email.
 */
Schedule::command('subscribers:send-weekly')->weeklyOn(4, '09:00')->withoutOverlapping();

/*
 * SCHEDULED CAMPAIGNS. Every minute, because "send at 09:00" has to mean 09:00 and not "some time
 * in the next hour". It is safe at that frequency: `CampaignDispatcher` claims each campaign with
 * a conditional update before writing anything, so overlapping runs find nothing left to claim
 * rather than sending everything twice.
 */
Schedule::command('campaigns:dispatch')->everyMinute()->withoutOverlapping();
