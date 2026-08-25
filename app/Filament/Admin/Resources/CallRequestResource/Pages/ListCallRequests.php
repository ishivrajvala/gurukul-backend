<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CallRequestResource\Pages;

use App\Filament\Admin\Resources\CallRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCallRequests extends ListRecords
{
    protected static string $resource = CallRequestResource::class;

    protected function getHeaderActions(): array
    {
        /* Nothing to create: call requests arrive from the site. */
        return [];
    }
}
