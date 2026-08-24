<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SocialLinkResource\Pages;

use App\Filament\Admin\Resources\SocialLinkResource;
use Filament\Resources\Pages\EditRecord;

class EditSocialLink extends EditRecord
{
    protected static string $resource = SocialLinkResource::class;

    /* Deleting a platform is not a thing — clearing its URL is. See the resource note. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
