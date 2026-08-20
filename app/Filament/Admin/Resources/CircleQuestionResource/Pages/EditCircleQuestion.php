<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleQuestionResource\Pages;

use App\Filament\Admin\Resources\CircleQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleQuestion extends EditRecord
{
    protected static string $resource = CircleQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
