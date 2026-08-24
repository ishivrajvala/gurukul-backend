<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SubscriberResource\Pages;

use App\Filament\Admin\Resources\SubscriberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriber extends EditRecord
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        /*
         * NO DELETE ACTION, and its absence is the point.
         *
         * Deleting a subscriber forgets that they ever asked to stop, and the next signup, import
         * or re-seed puts them straight back on the list. Unsubscribing is the operation people
         * mean when they reach for delete here; it is on the row in the table.
         */
        return [];
    }
}
