<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MostAskedResource\Pages;

use App\Filament\Admin\Resources\MostAskedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMostAsked extends EditRecord
{
    protected static string $resource = MostAskedResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
