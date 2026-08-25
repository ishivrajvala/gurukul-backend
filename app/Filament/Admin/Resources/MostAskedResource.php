<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MostAskedResource\Pages;
use App\Models\MostAsked;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Most asked" — the questions beside the Journal search, in the words a parent uses.
 *
 * A QUESTION IS NOT A TITLE. That is why this is a table and not a checkbox on the article: the
 * title is written for somebody who has already arrived, the question for somebody who has not.
 * Editing one must not touch the other.
 *
 * THE ARTICLE IS REQUIRED. Each row is a link and nothing else, so a question with no destination
 * is a dead end dressed as an answer. The picker offers published articles only, and the table
 * marks any row whose article has since been unpublished — the API drops those, so it goes quiet on
 * the site rather than 404ing, and this column is the only place that would ever tell you.
 */
class MostAskedResource extends Resource
{
    protected static ?string $model = MostAsked::class;

    /*
     * NOT IN THE MENU.
     *
     * These are the Journal's search furniture — ten questions and six terms, edited when the
     * archive changes and not otherwise. As top-level rows they cost two of the sidebar's twenty-two
     * lines every day to serve a job somebody does twice a year, and they sat at the same level as
     * Articles, which is the thing this section is actually about.
     *
     * The screens are unchanged and are reached from the Articles list, where somebody already is
     * when they think about them.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Content System';

    protected static ?string $navigationLabel = 'Most asked';

    protected static ?string $modelLabel = 'most asked question';

    /* Set by hand: the default pluraliser makes "most-askeds" out of this one. */
    protected static ?string $slug = 'most-asked';

    protected static ?string $pluralModelLabel = 'most asked questions';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('question')
                ->helperText('How a parent would say it, not how the article is titled. Keep it short enough to read at a glance.')
                ->required()
                ->maxLength(160)
                ->columnSpanFull(),

            Forms\Components\Select::make('article_id')
                ->label('Answered by')
                ->relationship('article', 'title', fn ($query) => $query->published())
                ->helperText('Published articles only. A question that leads nowhere is worse than one that is not listed.')
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('position')
                ->helperText('Lowest first. The rail shows the first ten before "View all most asked".')
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

                Tables\Columns\TextColumn::make('question')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('article.title')
                    ->label('Answered by')
                    ->wrap()
                    ->searchable()
                    /* The row survives its article being unpublished, and nothing else would say
                       so: the API filters it out, so the question simply stops appearing. */
                    ->description(fn (MostAsked $record): ?string => $record->article
                        && $record->article->status === 'published'
                        ? null
                        : 'Not published — this question is currently hidden on the site.')
                    ->color(fn (MostAsked $record): ?string => $record->article
                        && $record->article->status === 'published'
                        ? null
                        : 'warning'),
            ])
            ->filters([
                Tables\Filters\Filter::make('hidden')
                    ->label('Hidden on the site')
                    ->query(fn ($query) => $query->whereDoesntHave(
                        'article',
                        fn ($q) => $q->published(),
                    )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMostAsked::route('/'),
            'create' => Pages\CreateMostAsked::route('/create'),
            'edit' => Pages\EditMostAsked::route('/{record}/edit'),
        ];
    }
}
