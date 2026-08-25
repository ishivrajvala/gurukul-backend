<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleTopicResource\Pages;

use App\Filament\Admin\Resources\CircleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCircleTopics extends ListRecords
{
    protected static string $resource = CircleTopicResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
