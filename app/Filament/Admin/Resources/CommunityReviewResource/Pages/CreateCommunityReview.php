<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CommunityReviewResource\Pages;

use App\Filament\Admin\Resources\CommunityReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityReview extends CreateRecord
{
    protected static string $resource = CommunityReviewResource::class;
}
