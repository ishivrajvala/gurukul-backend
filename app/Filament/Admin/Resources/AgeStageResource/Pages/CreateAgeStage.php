<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AgeStageResource\Pages;

use App\Filament\Admin\Resources\AgeStageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgeStage extends CreateRecord
{
    protected static string $resource = AgeStageResource::class;
}
