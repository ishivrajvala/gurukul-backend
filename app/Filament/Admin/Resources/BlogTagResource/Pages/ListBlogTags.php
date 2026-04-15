<?php

namespace App\Filament\Admin\Resources\BlogTagResource\Pages;

use App\Filament\Admin\Resources\BlogTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlogTags extends ListRecords
{
    protected static string $resource = BlogTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
