<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CallRequestResource\Pages;

use App\Filament\Admin\Resources\CallRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallRequest extends EditRecord
{
    protected static string $resource = CallRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
