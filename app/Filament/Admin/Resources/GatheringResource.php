<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GatheringResource\Pages;
use App\Models\Gathering;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * One facilitated hour inside a Circle.
 *
 * `taken` IS FOR THE FACILITATOR, NOT THE PAGE. The site never prints "8 of 12 places left" — the
 * locked rule bans enrollment counts, and a small facilitated group is exactly the thing that
 * should not read as a filling scoreboard. It renders one of three phrases instead, and this form
 * says so, so nobody wonders why their number is not showing up.
 *
 * The topic sits on the GATHERING, not inherited from the Circle: "Screens, Attention & Modern
 * Childhood" runs for six-year-olds and for teenagers, and they are not the same hour.
 */
class GatheringResource extends Resource
{
    protected static ?string $model = Gathering::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Circles';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The gathering')->schema([
                Forms\Components\Select::make('circle_id')
                    ->relationship('circle', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->label('Circle'),

                Forms\Components\Select::make('topic_id')
                    ->relationship('topic', 'name')
                    ->preload()
                    ->label('Topic')
                    ->helperText('What this hour is about, which may differ from the Circle as a whole.'),

                Forms\Components\TextInput::make('title')->required()->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->helperText('What the conversation will actually be about. Not a syllabus.')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),

                Forms\Components\Select::make('ageStages')
                    ->relationship('ageStages', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Age stages')
                    ->helperText('Leave empty for a gathering open to every stage.'),
            ])->columns(2),

            Forms\Components\Section::make('When and where')->schema([
                Forms\Components\DateTimePicker::make('starts_at')
                    ->required()
                    ->seconds(false)
                    ->label('Starts at')
                    ->helperText('Anything in the past moves to the Circle\'s "already held" list automatically.'),

                Forms\Components\Select::make('format')
                    ->options(['online' => 'Online', 'in-person' => 'In person'])
                    ->default('online')
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('city')
                    ->helperText('Named on the card. "In person" on its own raises exactly the question it fails to answer.')
                    ->visible(fn (Forms\Get $get): bool => $get('format') === 'in-person')
                    ->requiredIf('format', 'in-person'),
            ])->columns(3),

            Forms\Components\Section::make('Places')->schema([
                Forms\Components\TextInput::make('capacity')->numeric()->default(12)->required(),

                Forms\Components\TextInput::make('taken')
                    ->numeric()
                    ->default(0)
                    ->helperText('For you, not for the page. The site shows "Places available", "A few places left" or "Fully booked" — never a count.'),

                Forms\Components\Toggle::make('is_published')->label('Published')->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')->dateTime('D j M, H:i')->sortable()->label('When'),
                Tables\Columns\TextColumn::make('title')->searchable()->weight('semibold')->limit(40),
                Tables\Columns\TextColumn::make('circle.name')->label('Circle')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('format')
                    ->badge()
                    ->formatStateUsing(fn ($record): string => $record->formatLabel())
                    ->color(fn ($record): string => $record->format === 'online' ? 'success' : 'primary'),
                Tables\Columns\TextColumn::make('availability')
                    ->label('Places')
                    ->state(fn ($record): string => $record->availabilityLabel()),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn ($query) => $query->where('starts_at', '>=', now()))
                    ->default(),
                Tables\Filters\SelectFilter::make('circle')->relationship('circle', 'name'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGatherings::route('/'),
            'create' => Pages\CreateGathering::route('/create'),
            'edit' => Pages\EditGathering::route('/{record}/edit'),
        ];
    }
}
