<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArticleTopicResource\Pages;

use App\Filament\Admin\Resources\ArticleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleTopic extends CreateRecord
{
    protected static string $resource = ArticleTopicResource::class;
}
