<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

class ActivateTheme extends Command
{
    protected $signature = 'activate:theme';

    protected $description = 'Activate the current theme based on date range.';

    public function handle(ThemeService $themeService): int
    {
        $themeService->activateCurrentTheme();

        $this->info('Theme activation completed.');

        return self::SUCCESS;
    }
}
