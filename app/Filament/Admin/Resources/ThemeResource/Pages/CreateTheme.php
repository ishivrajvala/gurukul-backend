<?php

namespace App\Filament\Admin\Resources\ThemeResource\Pages;

use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;
use App\Filament\Admin\Resources\ThemeResource;

class CreateTheme extends CreateRecordRedirectToIndex
{
    protected static string $resource = ThemeResource::class;
}
