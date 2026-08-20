<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleQuestionResource\Pages;

use App\Filament\Admin\Resources\CircleQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListCircleQuestions extends ListRecords
{
    protected static string $resource = CircleQuestionResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: these arrive from the website. */
        return [];
    }
}
