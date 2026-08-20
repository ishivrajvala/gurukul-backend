<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryResource\Pages;

use App\Filament\Admin\Resources\StoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStory extends EditRecord
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
