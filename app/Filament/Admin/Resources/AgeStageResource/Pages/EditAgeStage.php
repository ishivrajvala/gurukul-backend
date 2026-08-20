<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AgeStageResource\Pages;

use App\Filament\Admin\Resources\AgeStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgeStage extends EditRecord
{
    protected static string $resource = AgeStageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
