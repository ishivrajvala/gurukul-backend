<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArticleResource\Pages;

use App\Filament\Admin\Resources\ArticleResource;
use App\Filament\Admin\Resources\MostAskedResource;
use App\Filament\Admin\Resources\TrendingSearchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    /**
     * The Journal's search furniture, reachable from the Journal.
     *
     * MOST ASKED AND TRENDING SEARCHES LEFT THE SIDEBAR. They are ten questions and six terms,
     * edited when the archive changes and not otherwise — as top-level rows they cost two of the
     * menu's lines every day to serve a job somebody does twice a year, and they sat at the same
     * level as Articles, which is what that section is actually about.
     *
     * They are here instead, because this is the page somebody is already on when they think about
     * either of them. Hidden from navigation is not the same as unreachable: a screen with no way
     * in is a screen the next person who needs it quietly rebuilds.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mostAsked')
                ->label('Most asked')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->url(MostAskedResource::getUrl()),

            Actions\Action::make('trending')
                ->label('Trending searches')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->url(TrendingSearchResource::getUrl()),

            Actions\CreateAction::make(),
        ];
    }
}
