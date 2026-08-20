<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleResource\Pages;
use App\Models\Circle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Parent Circles: the standing, moderated spaces.
 *
 * A CIRCLE IS A PLACE, NOT AN EVENT — joinable whether or not anything is scheduled. The dated
 * hours live in Gatherings, which is a separate resource for that reason.
 *
 * NO AGE STAGES MEANS EVERY AGE. The field says so, because an empty multi-select otherwise reads
 * as "not filled in yet" and somebody will helpfully tick all six — which is not the same record.
 *
 * There is nothing here that counts members, activity or popularity, and nothing should be added.
 * A circle with a temperature reading on it stops being a held space.
 */
class CircleResource extends Resource
{
    protected static ?string $model = Circle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Circles';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The Circle')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(120)->columnSpanFull(),

                Forms\Components\TextInput::make('purpose')
                    ->helperText('One line, shown on the card: what this Circle is for.')
                    ->required()
                    ->maxLength(160)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->helperText('The longer welcome, shown when a parent opens the Circle.')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the URL. Avoid changing it once the Circle is live.'),

                Forms\Components\TextInput::make('position')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Who and what it is for')->schema([
                Forms\Components\Select::make('ageStages')
                    ->relationship('ageStages', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Age stages')
                    ->helperText('Leave EMPTY for a Circle that is for every stage — that is what makes the universal Circle universal. Ticking all six is a different record and reads differently on the site.'),

                Forms\Components\Select::make('topics')
                    ->relationship('topics', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('The shared topics. The same ten the Journal and Stories file under.'),

                Forms\Components\Select::make('petals')
                    ->relationship('petals', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('Which of the Nine Petals this Circle feeds. Recorded, not shown to parents: a family should not have to read the architecture before joining a conversation.')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Publishing')->schema([
                Forms\Components\Toggle::make('is_published')->label('Published')->default(true),

                Forms\Components\Toggle::make('managed_by_avdhara')
                    ->label('Held by Avdhara')
                    ->default(true)
                    ->helperText('Shows the "Managed by Avdhara" badge. The one status mark on a Circle.'),

                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured — "A good place to begin"')
                    ->helperText('Exactly one Circle should carry this. It is the universal starting place, so a first-time parent always has a correct answer to "where do I start".'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('purpose')->limit(50)->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('gatherings_count')->counts('gatherings')->label('Gatherings'),
                Tables\Columns\IconColumn::make('is_featured')->label('Start here')->boolean(),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircles::route('/'),
            'create' => Pages\CreateCircle::route('/create'),
            'edit' => Pages\EditCircle::route('/{record}/edit'),
        ];
    }
}
