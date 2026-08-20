<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleResource\Pages;

use App\Filament\Admin\Resources\CircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircle extends EditRecord
{
    protected static string $resource = CircleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
