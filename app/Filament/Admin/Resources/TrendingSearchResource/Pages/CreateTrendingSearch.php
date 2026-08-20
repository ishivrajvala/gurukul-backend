<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TrendingSearchResource\Pages;

use App\Filament\Admin\Resources\TrendingSearchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrendingSearch extends CreateRecord
{
    protected static string $resource = TrendingSearchResource::class;
}
