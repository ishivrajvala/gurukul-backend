<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StoryTagResource\Pages;

use App\Filament\Admin\Resources\StoryTagResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStoryTag extends CreateRecord
{
    protected static string $resource = StoryTagResource::class;
}
