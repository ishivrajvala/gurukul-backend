<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HomeStatResource\Pages;

use App\Filament\Admin\Resources\HomeStatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeStat extends EditRecord
{
    protected static string $resource = HomeStatResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
