<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Support\CampaignDispatcher;
use Illuminate\Console\Command;

/**
 * Send the campaigns whose scheduled time has passed.
 *
 * RUNS EVERY MINUTE, and it is safe to. `CampaignDispatcher` claims each campaign with a
 * conditional update before it writes anything, so a run that overlaps the previous one finds
 * nothing left to claim rather than sending everything twice.
 */
class DispatchDueCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch';

    protected $description = 'Send any campaign whose scheduled time has arrived';

    public function handle(): int
    {
        $due = Campaign::query()->due()->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $campaign) {
            $count = CampaignDispatcher::dispatch($campaign);

            $this->info(sprintf('"%s" → %d recipient(s).', $campaign->subject, $count));
        }

        return self::SUCCESS;
    }
}
