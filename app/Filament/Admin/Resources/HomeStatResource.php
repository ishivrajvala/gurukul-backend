<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HomeStatResource\Pages;
use App\Models\HomeStat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The three figures in the homepage's "You are not alone" band.
 *
 * THESE ARE CLAIMS ABOUT THE WORLD, not about Avdhara. "1 in 3 children show signs of attention
 * difficulty by age 8" is research; it is not a rating or a number of families, which is why this
 * band is allowed a number at all when the rest of the site refuses one.
 *
 * That only holds while they are true. Record where each came from in Source — nothing renders it,
 * it is there so the next person can check rather than inherit a figure nobody can trace.
 *
 * COLOUR IS NOT YOURS TO PICK. First indigo, second orange, third green, by position. The palette
 * is six colours and it stays six.
 */
class HomeStatResource extends Resource
{
    protected static ?string $model = HomeStat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Homepage figures';

    protected static ?string $modelLabel = 'figure';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('figure')
                ->helperText('The number as it should read: "73%", "2.4x", "1 in 3". Its shape is the point, so it is typed rather than calculated.')
                ->required()
                ->maxLength(24),

            Forms\Components\Select::make('icon')
                ->options([
                    'people' => 'People',
                    'phone' => 'Phone',
                    'brain' => 'Brain',
                    'book' => 'Book',
                    'leaf' => 'Leaf',
                ])
                ->required()
                ->native(false),

            Forms\Components\Textarea::make('description')
                ->helperText('What the figure is about, continuing from it: "of parents feel their child is overstimulated but do not know what to do."')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('source')
                ->helperText('Where this came from. Not shown on the site — it is here so the figure can be checked rather than inherited.')
                ->maxLength(300)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_published')->label('Published')->default(true),

            Forms\Components\TextInput::make('position')
                ->helperText('Lowest first. Also picks the colour: 1 indigo, 2 orange, 3 green.')
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
                Tables\Columns\TextColumn::make('figure')->weight('bold'),
                Tables\Columns\TextColumn::make('description')->wrap()->limit(90),
                Tables\Columns\TextColumn::make('source')
                    ->placeholder('No source recorded')
                    ->color(fn (?string $state): string => filled($state) ? 'gray' : 'warning')
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeStats::route('/'),
            'create' => Pages\CreateHomeStat::route('/create'),
            'edit' => Pages\EditHomeStat::route('/{record}/edit'),
        ];
    }
}
