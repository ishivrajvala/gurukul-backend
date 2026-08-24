<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CommunityReviewResource\Pages;

use App\Filament\Admin\Resources\CommunityReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommunityReviews extends ListRecords
{
    protected static string $resource = CommunityReviewResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
