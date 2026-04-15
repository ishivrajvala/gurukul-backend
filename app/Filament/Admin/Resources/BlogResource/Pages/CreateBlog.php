<?php

namespace App\Filament\Admin\Resources\BlogResource\Pages;

use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;
use App\Filament\Admin\Resources\BlogResource;

class CreateBlog extends CreateRecordRedirectToIndex
{
    protected static string $resource = BlogResource::class;
}
