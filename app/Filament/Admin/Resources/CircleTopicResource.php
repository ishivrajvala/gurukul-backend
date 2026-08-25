<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleTopicResource\Pages;
use App\Models\CircleTopic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * What a Parent Circle, and each of its gatherings, is about.
 *
 * THESE ARE THE ORIGINAL TEN, kept deliberately. When the shared taxonomy was split three ways, the
 * Journal took a new fifteen chosen for search and Stories were re-tagged around outcomes — but the
 * ten were designed for this job first, and all six circles already sit across them cleanly.
 * Renaming them to match the Journal would have been churn that helped nobody.
 *
 * A circle carries SEVERAL of these; a gathering carries one.
 */
class CircleTopicResource extends Resource
{
    protected static ?string $model = CircleTopic::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationGroup = 'Parenting';

    /** Directly under Circles. */
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Circle topics';

    protected static ?string $modelLabel = 'circle topic';

    protected static ?string $pluralModelLabel = 'circle topics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Short name')
                ->helperText('The label under an icon, e.g. "Behaviour".')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('full_name')
                ->helperText('The heading when a reader filters to this topic, e.g. "Behaviour & Emotions".')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('eyebrow')
                ->helperText('The uppercase form above a circle or gathering title.')
                ->required()
                ->maxLength(120),

            Forms\Components\Textarea::make('description')
                ->helperText('One sentence, shown when a reader filters to this topic.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->helperText('The identifier the website matches on, and the icon it draws. Changing it orphans every circle and gathering already filed here.')
                ->required()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('position')
                ->helperText('Locked nav order. Never sorted alphabetically.')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('full_name')->label('Full name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('slug')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('circles_count')->counts('circles')->label('Circles'),
                Tables\Columns\TextColumn::make('gatherings_count')->counts('gatherings')->label('Gatherings'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleTopics::route('/'),
            'create' => Pages\CreateCircleTopic::route('/create'),
            'edit' => Pages\EditCircleTopic::route('/{record}/edit'),
        ];
    }
}
