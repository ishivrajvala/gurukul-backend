<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StorySubmissionResource\Pages;

use App\Filament\Admin\Resources\StorySubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListStorySubmissions extends ListRecords
{
    protected static string $resource = StorySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: these arrive from the website. */
        return [];
    }
}
