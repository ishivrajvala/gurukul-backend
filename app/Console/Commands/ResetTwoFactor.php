<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * THE WAY BACK IN. Clears somebody's second factor so they can enrol again.
 *
 *   php artisan admin:reset-2fa somebody@gurukul2.com
 *
 * WHY THIS HAS TO EXIST. Two-factor is mandatory for every account here, which means a lost,
 * reset or stolen phone is a locked-out person — and if the only answer is "sorry", the pressure to
 * simply turn the whole feature off arrives within the week. Recovery codes handle most of it; this
 * handles the rest.
 *
 * DELIBERATELY NOT A BUTTON IN THE PANEL. An admin who can clear another admin's second factor from
 * a web page is a single compromised session away from clearing everybody's — it would turn MFA
 * into something an attacker can switch off from inside. Requiring shell access on the server means
 * the escape hatch is guarded by a different credential from the one it can bypass.
 *
 * It does NOT confirm anything: the account comes back unenrolled, so the next sign-in walks
 * through setup and produces a fresh secret and a fresh set of recovery codes.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email : The account to reset}';

    protected $description = 'Clear an account\'s two-factor so it can be set up again';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error('No account with that address.');

            return self::FAILURE;
        }

        if (! $this->confirm("Clear two-factor for {$user->name} <{$user->email}>?", false)) {
            $this->info('Nothing changed.');

            return self::SUCCESS;
        }

        $user->resetTwoFactor();

        $this->info('Done. They will be asked to set it up again at their next sign-in.');
        $this->warn('Tell them in person or on a channel you trust — not by replying to whoever asked.');

        return self::SUCCESS;
    }
}
