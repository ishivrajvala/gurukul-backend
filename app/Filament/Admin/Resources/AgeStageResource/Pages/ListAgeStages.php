<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AgeStageResource\Pages;

use App\Filament\Admin\Resources\AgeStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgeStages extends ListRecords
{
    protected static string $resource = AgeStageResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
