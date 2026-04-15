<?php

namespace App\Filament\Admin\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;

abstract class CreateRecordRedirectToIndex extends CreateRecord
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
