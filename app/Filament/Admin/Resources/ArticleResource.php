<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ArticleResource\Pages;
use App\Models\Article;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * The Parent Journal. Formerly BlogResource.
 *
 * TITLES ARE THE QUESTIONS PARENTS ACTUALLY TYPE — "Should My Five-Year-Old Already Be Reading?" —
 * not the subject a librarian would file it under. That is the whole taxonomy decision: a parent
 * arrives with the question, and the topic is the quiet label above it. The title field says so,
 * because it is the single thing most likely to drift back toward headline-writing.
 *
 * THE EDITOR IS TIPTAP, which is what the frontend already expects: `journal-content.ts` documents
 * the body as `TiptapOutput::Html` and the site has exactly one component that renders it, through
 * a sanitiser. Anything that produced a different shape here would break that contract silently.
 *
 * STATUS AND BODY ARE TWO DIFFERENT QUESTIONS, which is worth knowing before editing here.
 * `status` governs whether a piece is LISTED at all; an empty body governs what its own page says.
 * Thirty-five of the thirty-six imported articles are published and unwritten — they appear in the
 * feed with a title, a topic and an age span, and their reading page says plainly that the piece is
 * still being written while the route stays out of search. That is deliberate on the site, not a
 * broken state, which is why the table has a "Still to write" filter rather than hiding them.
 */
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    /*
     * FOUND BY, and CALLED. Global search stays off until a resource answers both: `$recordTitle`
     * is what a result reads as in the list, and the attributes are what it matches on.
     *
     * Deliberately narrow. Searching a body of text finds every article that mentions a word, which
     * is a research tool rather than a way to reach the one record somebody has in mind.
     */
    protected static ?string $recordTitleAttribute = 'title';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'standfirst'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Article: '.\Illuminate\Support\Str::limit((string) $record->title, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Content System';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The piece')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->live(onBlur: true)
                    ->helperText('The question a parent would actually type, near enough verbatim. Not a headline.')
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation): void {
                        /* Only on create. Regenerating a slug on edit changes a live URL and breaks
                           every link to it, which is a much bigger cost than a slug that no longer
                           matches a retitled piece. */
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Set from the title when the piece is created, and never changed after: it is a live URL.'),

                Forms\Components\TextInput::make('reading_minutes')
                    ->numeric()
                    ->default(5)
                    ->required()
                    ->label('Reading time (minutes)'),

                Forms\Components\Textarea::make('standfirst')
                    ->required()
                    ->rows(3)
                    ->maxLength(400)
                    ->helperText('The paragraph under the title. It should answer the question honestly enough to be useful on its own.')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Filing')->schema([
                Forms\Components\Select::make('topic_id')
                    ->relationship('topic', 'name')
                    ->required()
                    ->preload()
                    ->label('Topic')
                    ->helperText('One of the shared ten. The same list Circles and Stories file under.'),

                Forms\Components\Select::make('ageStages')
                    ->relationship('ageStages', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Age stages')
                    ->helperText('Every stage the piece applies to. The eyebrow shows the span; the filter matches any one of them.'),
            ])->columns(2),

            Forms\Components\Section::make('The article')->schema([
                /*
                 * TiptapEditor, matching what the frontend renders. The profile is left at the
                 * package default deliberately: `journal-content.ts` documents the site as handling
                 * the DEFAULT toolbar's full output — headings, lists, blockquotes, rules, tables,
                 * details blocks — so narrowing it here would be a promise the frontend does not
                 * need and widening it would produce markup nothing renders.
                 */
                TiptapEditor::make('content')
                    ->label('Body')
                    ->profile('default')
                    ->helperText('Leave empty while the piece is commissioned but unwritten. The site says so plainly rather than showing a blank article, and the route stays out of search.')
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Publishing')->schema([
                Forms\Components\Select::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                    ->default('draft')
                    ->required()
                    ->helperText('Draft hides the piece entirely. A published piece with no body still appears in the feed, and its own page says it is being written.'),

                Forms\Components\DateTimePicker::make('published_at')
                    ->seconds(false)
                    ->helperText('The site shows nothing whose date has not yet passed.'),

                Forms\Components\Toggle::make('is_featured')
                    ->label('Worth reading first')
                    ->helperText('Editors pick these. Never computed from traffic.'),

                Forms\Components\FileUpload::make('featured_image')
                    ->image()
                    ->directory('journal')
                    ->disk('public')
                    ->label('Photograph'),

                Forms\Components\TextInput::make('featured_image_alt')
                    ->label('Photograph description')
                    ->helperText('What the photograph shows, for a reader who cannot see it. Required before publishing with an image.')
                    ->maxLength(200)
                    ->columnSpanFull(),
            ])->columns(3),

            /*
             * WHAT GOOGLE SHOWS, when it should differ from what the page shows.
             *
             * Left empty, a result uses the article's own headline and standfirst — and those are
             * written for somebody who has already arrived. "Should My Five-Year-Old Already Be
             * Reading?" reads well at the top of a page and competes badly in a list of ten blue
             * links; a standfirst is a lead-in, not a summary that has to work alone.
             *
             * Every field falls back, so an article with none of this behaves exactly as before.
             * Write one when the page title is genuinely the wrong thing to show in a result.
             */
            Forms\Components\Section::make('Search results')
                ->description('Optional. Left blank, the article\'s own title and standfirst are used.')
                ->relationship('seo')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Title')
                        ->helperText('Around 60 characters. Longer is not an error — Google simply cuts it, so put what matters first.')
                        ->maxLength(180)
                        ->live(debounce: 400)
                        ->hintColor(fn (?string $state): string => strlen((string) $state) > 60 ? 'warning' : 'gray')
                        ->hint(fn (?string $state): string => strlen((string) $state).' characters')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('meta_description')
                        ->label('Description')
                        ->helperText('Around 155 characters. A sentence that answers the question the title asks, written for somebody deciding whether to open it.')
                        ->rows(3)
                        ->maxLength(400)
                        ->live(debounce: 400)
                        ->hintColor(fn (?string $state): string => strlen((string) $state) > 155 ? 'warning' : 'gray')
                        ->hint(fn (?string $state): string => strlen((string) $state).' characters')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('og_image')
                        ->label('Share image')
                        ->helperText('The picture used when the link is shared. Leave blank to use the site default.')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->helperText('Only when this piece is published somewhere else first. Wrong values here remove the page from search entirely, so leave it blank unless you are sure.')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->limit(52)->weight('semibold')->wrap(),
                Tables\Columns\TextColumn::make('topic.name')->label('Topic')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('ageStages.range_label')
                    ->label('Ages')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'warning'),
                /* "Written" rather than a body preview: the useful question about thirty-six rows is
                   which of them still need copy. */
                Tables\Columns\IconColumn::make('content')
                    ->label('Written')
                    ->boolean()
                    ->state(fn (Article $record): bool => filled($record->content)),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('published_at')->date('j M Y')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                Tables\Filters\SelectFilter::make('topic')->relationship('topic', 'name')->preload(),
                Tables\Filters\Filter::make('unwritten')
                    ->label('Still to write')
                    ->query(fn ($query) => $query->whereNull('content')),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
