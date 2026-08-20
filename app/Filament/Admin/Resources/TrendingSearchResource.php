<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TrendingSearchResource\Pages;
use App\Models\TrendingSearch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The terms under the Journal search field.
 *
 * EDITORIAL, NOT MEASURED. Nothing counts what visitors search for. "Trending" is the site's word
 * for "start here", and this is a short hand-picked list of ways in — not a leaderboard, and not
 * something to grow to twenty.
 *
 * A TERM MUST FIND SOMETHING. Pressing one runs the ordinary search across article titles and
 * standfirsts, so a term nothing matches is a button that empties the page. Nothing here can check
 * that for you; search the Journal for a new term before adding it.
 */
class TrendingSearchResource extends Resource
{
    protected static ?string $model = TrendingSearch::class;

    /* Not in the menu — reached from the Articles list. See MostAskedResource for why. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Content System';

    protected static ?string $navigationLabel = 'Trending searches';

    protected static ?string $modelLabel = 'trending search';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('term')
                ->helperText('Two words at most, lowercase, as a parent would type it: "screen time", "tantrums". Search the Journal for it first — a term that matches nothing empties the page.')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('position')
                ->helperText('Lowest first. Six or so is the right number; a long list stops reading as a suggestion.')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('term')->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrendingSearches::route('/'),
            'create' => Pages\CreateTrendingSearch::route('/create'),
            'edit' => Pages\EditTrendingSearch::route('/{record}/edit'),
        ];
    }
}
