<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleSignupResource\Pages;

use App\Filament\Admin\Resources\CircleSignupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCircleSignup extends EditRecord
{
    protected static string $resource = CircleSignupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
