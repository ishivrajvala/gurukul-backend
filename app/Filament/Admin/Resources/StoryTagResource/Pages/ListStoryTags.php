<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryTagResource\Pages;

use App\Filament\Admin\Resources\StoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoryTags extends ListRecords
{
    protected static string $resource = StoryTagResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
