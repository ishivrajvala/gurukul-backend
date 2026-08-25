<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArticleTopicResource\Pages;

use App\Filament\Admin\Resources\ArticleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticleTopic extends EditRecord
{
    protected static string $resource = ArticleTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
