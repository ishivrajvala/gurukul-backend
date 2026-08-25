<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StoryTagResource\Pages;
use App\Models\StoryTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * What a Parent Story is about.
 *
 * TAGS, NOT TOPICS. Stories used to file against the same subject list as articles, which produced
 * labels that were true and useless: a story about a family who stopped checking homework every
 * evening was filed under "Learning", exactly as an article about reading levels would be. A story
 * is not about a subject. It is about something a family changed.
 *
 * So these are outcome-shaped — "Letting go of control", "Learning without pressure" — and a reader
 * browsing them is choosing a situation they recognise rather than a topic they want to study.
 *
 * NOT THE SAME FIELD AS THE STORY'S EYEBROW, which sits on the story itself and answers a different
 * question again: what the story turns out to be about developmentally. A tag groups; an eyebrow
 * describes. Both exist on purpose, and the story form says so.
 */
class StoryTagResource extends Resource
{
    protected static ?string $model = StoryTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = 'Parenting';

    /** Directly under Stories. */
    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Story tags';

    protected static ?string $modelLabel = 'story tag';

    protected static ?string $pluralModelLabel = 'story tags';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Short name')
                ->helperText('The label on a filter chip, e.g. "Letting go of control".')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('full_name')
                ->helperText('The heading when a reader filters to this tag.')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('eyebrow')
                ->helperText('The uppercase form above a story title. Not the same as the story\'s own eyebrow, which describes that one story.')
                ->required()
                ->maxLength(120),

            Forms\Components\Textarea::make('description')
                ->helperText('One sentence, shown when a reader filters to this tag.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->helperText('The identifier the website matches on. Changing it orphans every story already tagged here.')
                ->required()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('position')
                ->helperText('Hand-ordered. Never sorted alphabetically.')
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
                Tables\Columns\TextColumn::make('stories_count')->counts('stories')->label('Stories'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoryTags::route('/'),
            'create' => Pages\CreateStoryTag::route('/create'),
            'edit' => Pages\EditStoryTag::route('/{record}/edit'),
        ];
    }
}
