<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Filament\Notifications\Notification;

/**
 * Tell whoever has the panel open that something arrived.
 *
 * A BELL WITH NOTHING BEHIND IT IS WORSE THAN NO BELL. Filament's notification centre is off by
 * default because it has nothing to show; turning it on and leaving it empty puts a control in the
 * header that trains people to ignore it. This is what fills it.
 *
 * EVERY SUBMISSION THE SITE TAKES RAISES ONE. That is the whole point of this admin: a family who
 * asked to join a Circle is waiting on a person, and a queue nobody looks at is the failure the
 * inbox screens are shaped around. Somebody with the panel open now learns about it without
 * refreshing a list they were not on.
 *
 * IT NEVER THROWS. A notification is a courtesy on top of a submission that has already been
 * stored; if the notifications table is missing or a query fails, the parent's message must still
 * be saved and the endpoint must still return 201. Losing a real submission to decorate the header
 * would be an absurd trade.
 */
final class NotifyAdmins
{
    public static function of(string $title, string $body, string $url, string $icon = 'heroicon-o-inbox-arrow-down'): void
    {
        try {
            /*
             * Everybody with a role, because everybody with a role can reach the panel — see
             * `User::canAccessPanel`. A per-role rule here would be a second, quieter definition of
             * who works here, and the two would drift.
             */
            $recipients = User::whereHas('roles')->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->iconColor('warning')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('open')
                        ->label('Open')
                        ->url($url)
                        ->markAsRead(),
                ])
                ->sendToDatabase($recipients);
        } catch (\Throwable $e) {
            /* The submission is already saved. Log it and carry on. */
            report($e);
        }
    }
}
