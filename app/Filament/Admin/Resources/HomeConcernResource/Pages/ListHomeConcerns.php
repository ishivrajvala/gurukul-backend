<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HomeConcernResource\Pages;

use App\Filament\Admin\Resources\HomeConcernResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeConcerns extends ListRecords
{
    protected static string $resource = HomeConcernResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
