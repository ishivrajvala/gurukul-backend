<?php

namespace App\Filament\Admin\Resources\BlogCategoryResource\Pages;

use App\Filament\Admin\Resources\BlogCategoryResource;
use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;

class CreateBlogCategory extends CreateRecordRedirectToIndex
{
    protected static string $resource = BlogCategoryResource::class;
}
