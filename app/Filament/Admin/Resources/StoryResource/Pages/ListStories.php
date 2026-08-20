<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryResource\Pages;

use App\Filament\Admin\Resources\StoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStories extends ListRecords
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
