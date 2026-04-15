<?php

namespace App\Filament\Admin\Resources\LandingPageResource\Pages;

use App\Filament\Admin\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
