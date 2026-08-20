<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TrendingSearchResource\Pages;

use App\Filament\Admin\Resources\TrendingSearchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrendingSearches extends ListRecords
{
    protected static string $resource = TrendingSearchResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
