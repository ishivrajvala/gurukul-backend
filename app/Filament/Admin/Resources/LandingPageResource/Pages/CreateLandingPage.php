<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LandingPageResource\Pages;

use App\Filament\Admin\Resources\LandingPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;
}
