<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PetalResource\Pages;
use App\Models\Petal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The Nine Petals.
 *
 * NINE, AND NOT EIGHT OR TEN. They are the framework the whole site is built on, not a taxonomy
 * somebody extends when a new theme comes up: a Story names one as its eyebrow, a Circle maps onto
 * them, and the pathway pages are written around the set. Adding a tenth here does nothing until
 * the frontend knows about it, and the frontend holds the locked names in `pathwayLibrary.ts`.
 *
 * SO WHY A SCREEN AT ALL. Because there was none, and nine rows that everything references were
 * invisible: an editor picking a petal on a story had no way to read what it means, and a typo in
 * a name shipped as an eyebrow on a family's story with nowhere to fix it. Wording is editable,
 * the slug is not, and creation is deliberately not offered.
 */
class PetalResource extends Resource
{
    protected static ?string $model = Petal::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Taxonomy';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')
                ->helperText('Its place in the nine. Locked order, never sorted alphabetically.')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->helperText('The name rendered on the site, e.g. "Attention & Self Mastery". It appears as the eyebrow on a story, so a typo here is a typo on a family\'s page.')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('slug')
                ->helperText('What the site matches on. Changing it breaks every story and circle already filed here.')
                ->required()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true),

            Forms\Components\Textarea::make('intro')
                ->helperText('One or two sentences on what this capacity actually is.')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\TextColumn::make('intro')->wrap()->limit(120),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        /* No create and no delete: there are Nine Petals. See the note above. */
        return [
            'index' => Pages\ListPetals::route('/'),
            'edit' => Pages\EditPetal::route('/{record}/edit'),
        ];
    }
}
