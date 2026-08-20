<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GatheringResource\Pages;

use App\Filament\Admin\Resources\GatheringResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGatherings extends ListRecords
{
    protected static string $resource = GatheringResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
