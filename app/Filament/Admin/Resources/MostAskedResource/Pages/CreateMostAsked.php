<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MostAskedResource\Pages;

use App\Filament\Admin\Resources\MostAskedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMostAsked extends CreateRecord
{
    protected static string $resource = MostAskedResource::class;
}
