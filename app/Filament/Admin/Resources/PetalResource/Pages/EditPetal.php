<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PetalResource\Pages;

use App\Filament\Admin\Resources\PetalResource;
use Filament\Resources\Pages\EditRecord;

class EditPetal extends EditRecord
{
    protected static string $resource = PetalResource::class;

    protected function getHeaderActions(): array
    {
        /* No delete: everything on the site references these nine. */
        return [];
    }
}
