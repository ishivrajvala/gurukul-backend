<?php

namespace App\Filament\Admin\Resources\LandingPageResource\Pages;

use App\Filament\Admin\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingPages extends ListRecords
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
