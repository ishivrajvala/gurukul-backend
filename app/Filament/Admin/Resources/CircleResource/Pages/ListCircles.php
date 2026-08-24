<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleResource\Pages;

use App\Filament\Admin\Resources\CircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircles extends ListRecords
{
    protected static string $resource = CircleResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
