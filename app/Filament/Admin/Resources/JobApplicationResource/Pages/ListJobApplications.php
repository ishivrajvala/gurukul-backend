<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\JobApplicationResource\Pages;

use App\Filament\Admin\Resources\JobApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListJobApplications extends ListRecords
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: applications arrive from the website. */
        return [];
    }
}
