<?php

namespace App\Filament\Admin\Resources\BlogTagResource\Pages;

use App\Filament\Admin\Resources\BlogTagResource;
use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;

class CreateBlogTag extends CreateRecordRedirectToIndex
{
    protected static string $resource = BlogTagResource::class;
}
