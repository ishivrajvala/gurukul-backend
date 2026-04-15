<?php

namespace App\Filament\Admin\Resources\LandingPageResource\Pages;

use App\Filament\Admin\Resources\LandingPageResource;
use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;

class CreateLandingPage extends CreateRecordRedirectToIndex
{
    protected static string $resource = LandingPageResource::class;
}
