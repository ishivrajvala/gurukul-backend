<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArticleTopicResource\Pages;

use App\Filament\Admin\Resources\ArticleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleTopics extends ListRecords
{
    protected static string $resource = ArticleTopicResource::class;

    protected function getHeaderActions(): array
    {
        /* The New button lives IN the table now — see AppServiceProvider. */
        return [];
    }
}
