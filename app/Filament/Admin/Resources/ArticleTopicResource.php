<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ArticleTopicResource\Pages;
use App\Models\ArticleTopic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The fifteen Parent Journal topics.
 *
 * IT SITS UNDER ARTICLES, NOT IN TAXONOMY, and that is the point of the move. This list used to be
 * the shared "Topics" table three modules filed against, parked in a Taxonomy group with age stages
 * and petals — reference data, edited once. It is not that any more. It is the Journal's own
 * category structure, chosen for what parents search, and the person editing it is the person
 * editing articles. It belongs next to them.
 *
 * EDITABLE, BUT NOT EXTENDABLE IN PRACTICE. The frontend files articles against these exact fifteen
 * slugs and draws an icon per slug, so adding a sixteenth here does nothing until the site knows
 * about it, and changing a slug orphans every article already filed under it. Wording and order are
 * what an editor actually wants; the slug is disabled after creation.
 */
class ArticleTopicResource extends Resource
{
    protected static ?string $model = ArticleTopic::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Content System';

    /** Directly under Articles. */
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Article topics';

    protected static ?string $modelLabel = 'article topic';

    protected static ?string $pluralModelLabel = 'article topics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Short name')
                ->helperText('The label under an icon, e.g. "Learning".')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('full_name')
                ->helperText('The heading of a topic page, e.g. "Reading, Writing & Maths".')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('eyebrow')
                ->helperText('The uppercase form above an article title. A third field on purpose: the design uses the long name for some topics and the short one for others, so it cannot be derived.')
                ->required()
                ->maxLength(120),

            Forms\Components\Textarea::make('description')
                ->helperText('One sentence, shown when a reader filters to this topic.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->helperText('The identifier the website matches on, and the icon it draws. Changing it orphans every article already filed here.')
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
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleTopics::route('/'),
            'create' => Pages\CreateArticleTopic::route('/create'),
            'edit' => Pages\EditArticleTopic::route('/{record}/edit'),
        ];
    }
}
