<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EnquiryResource\Pages;

use App\Filament\Admin\Resources\EnquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: these arrive from the website. */
        return [];
    }
}
