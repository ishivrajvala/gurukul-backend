<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SocialLinkResource\Pages;

use App\Filament\Admin\Resources\SocialLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListSocialLinks extends ListRecords
{
    protected static string $resource = SocialLinkResource::class;

    /* No create action: the platform set is fixed by what the site can draw. See the resource. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
