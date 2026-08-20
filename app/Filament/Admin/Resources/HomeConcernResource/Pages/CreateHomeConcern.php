<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HomeConcernResource\Pages;

use App\Filament\Admin\Resources\HomeConcernResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeConcern extends CreateRecord
{
    protected static string $resource = HomeConcernResource::class;
}
