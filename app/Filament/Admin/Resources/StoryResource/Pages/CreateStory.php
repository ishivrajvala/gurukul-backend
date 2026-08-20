<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryResource\Pages;

use App\Filament\Admin\Resources\StoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStory extends CreateRecord
{
    protected static string $resource = StoryResource::class;
}
