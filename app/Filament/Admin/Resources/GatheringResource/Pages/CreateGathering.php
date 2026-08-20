<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GatheringResource\Pages;

use App\Filament\Admin\Resources\GatheringResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGathering extends CreateRecord
{
    protected static string $resource = GatheringResource::class;
}
