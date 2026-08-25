<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleTopicResource\Pages;

use App\Filament\Admin\Resources\CircleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleTopic extends EditRecord
{
    protected static string $resource = CircleTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
