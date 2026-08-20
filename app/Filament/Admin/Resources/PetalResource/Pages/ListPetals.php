<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PetalResource\Pages;

use App\Filament\Admin\Resources\PetalResource;
use Filament\Resources\Pages\ListRecords;

class ListPetals extends ListRecords
{
    protected static string $resource = PetalResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: there are Nine Petals. */
        return [];
    }
}
