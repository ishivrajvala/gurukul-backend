<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\JobRoleResource\Pages;

use App\Filament\Admin\Resources\JobRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobRole extends EditRecord
{
    protected static string $resource = JobRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
