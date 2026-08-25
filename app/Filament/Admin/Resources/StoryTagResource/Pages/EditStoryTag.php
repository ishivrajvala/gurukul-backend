<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryTagResource\Pages;

use App\Filament\Admin\Resources\StoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoryTag extends EditRecord
{
    protected static string $resource = StoryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
