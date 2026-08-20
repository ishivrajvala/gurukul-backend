<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HomeStatResource\Pages;

use App\Filament\Admin\Resources\HomeStatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeStat extends CreateRecord
{
    protected static string $resource = HomeStatResource::class;
}
