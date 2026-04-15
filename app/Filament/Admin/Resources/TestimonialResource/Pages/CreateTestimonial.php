<?php

namespace App\Filament\Admin\Resources\TestimonialResource\Pages;

use App\Filament\Admin\Resources\TestimonialResource;
use App\Filament\Admin\Resources\Pages\CreateRecordRedirectToIndex;

class CreateTestimonial extends CreateRecordRedirectToIndex
{
    protected static string $resource = TestimonialResource::class;
}
