<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TopicResource\Pages;
use App\Models\Topic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The ten shared topics.
 *
 * EDITABLE, BUT NOT EXTENDABLE IN PRACTICE. The frontend files articles, circles and stories
 * against these exact ten slugs, so adding an eleventh here does nothing until the site knows about
 * it, and changing a slug breaks whatever already points at it. Wording and order are the fields an
 * editor actually wants; the slug is deliberately disabled after creation.
 */
class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Taxonomy';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Short name')
                ->helperText('The label under an icon, e.g. "Learning".')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('full_name')
                ->helperText('The heading of a topic page, e.g. "Learning & Academics".')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('eyebrow')
                ->helperText('The uppercase form above a title. A third field on purpose: the design uses the long name for some topics and the short one for others, so it cannot be derived.')
                ->required()
                ->maxLength(120),

            Forms\Components\Textarea::make('description')
                ->helperText('One sentence, shown when a reader filters to this topic.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->helperText('The identifier the website matches on. Changing it breaks every article, circle and story already filed here.')
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
                Tables\Columns\TextColumn::make('articles_count')->counts('articles')->label('Articles'),
                Tables\Columns\TextColumn::make('stories_count')->counts('stories')->label('Stories'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
