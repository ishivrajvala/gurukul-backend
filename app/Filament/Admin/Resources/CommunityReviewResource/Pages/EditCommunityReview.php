<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CommunityReviewResource\Pages;

use App\Filament\Admin\Resources\CommunityReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommunityReview extends EditRecord
{
    protected static string $resource = CommunityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
