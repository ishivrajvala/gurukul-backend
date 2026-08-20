<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StorySubmissionResource\Pages;

use App\Filament\Admin\Resources\StorySubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStorySubmission extends EditRecord
{
    protected static string $resource = StorySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
