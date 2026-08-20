<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleSignupResource\Pages;

use App\Filament\Admin\Resources\CircleSignupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleSignups extends ListRecords
{
    protected static string $resource = CircleSignupResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: signups come from the website. */
        return [];
    }
}
