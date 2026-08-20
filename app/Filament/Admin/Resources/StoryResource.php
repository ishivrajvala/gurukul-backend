<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StoryResource\Pages;
use App\Models\Story;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Parent Stories.
 *
 * ONE CONTENT TYPE, TWO SHAPES. A story is written or it is a video, and `media_kind` is the only
 * field that differs — a parent browsing should experience them as one thing called a Parent Story.
 * The form shows the video fields only when they apply, so nobody fills in a YouTube id for a piece
 * of prose.
 *
 * EVERY VIDEO IS A YOUTUBE EMBED. Families send us a file and Avdhara publishes it on its channel;
 * a self-hosted arm was tried on the site and removed, because a reader could not tell the two
 * apart and the only difference was ours to maintain. So there is a video ID here and no uploader.
 *
 * THE BODY IS PLAIN PARAGRAPHS, not rich text, and that is the one real difference from an Article.
 * A parent story is somebody talking: no headings, no lists, nothing a family would want to format
 * — and nothing for the site to sanitise, because there is no markup.
 */
class StoryResource extends Resource
{
    protected static ?string $model = Story::class;

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
        return ['title', 'author_name', 'place'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Story: '.\Illuminate\Support\Str::limit((string) $record->title, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Parenting';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The story')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->helperText('The parent\'s own sentence, near enough verbatim. The site renders it in quotation marks, so do not add them here.')
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation): void {
                        /* Create only: regenerating a slug on edit changes a live URL. */
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug(Str::limit($state, 60, '')));
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('reading_minutes')
                    ->numeric()
                    ->default(4)
                    ->required()
                    ->label('Reading time (minutes)')
                    ->helperText('Shown on written stories only. A video carries its duration instead.'),

                Forms\Components\Textarea::make('standfirst')
                    ->required()
                    ->rows(2)
                    ->maxLength(400)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('body')
                    ->label('The story, in paragraphs')
                    ->simple(Forms\Components\Textarea::make('paragraph')->rows(4)->required())
                    ->helperText('One box per paragraph. Deliberately plain: a parent story is somebody talking, so there is no formatting to apply and nothing for the site to sanitise.')
                    ->addActionLabel('Add a paragraph')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('The family')->schema([
                Forms\Components\TextInput::make('author_name')
                    ->label('Parent\'s name')
                    ->required()
                    ->helperText('First name only. Attribution is to the parent, never to Avdhara.'),

                Forms\Components\TextInput::make('author_relation')
                    ->label('Relation')
                    ->required()
                    ->placeholder('Parent of a 5-year-old'),

                Forms\Components\TextInput::make('place')
                    ->helperText('Their city, if they were happy to name it. Leaving it empty is a valid answer, not missing data.'),
            ])->columns(3),

            Forms\Components\Section::make('Filing')->schema([
                Forms\Components\Select::make('topic_id')
                    ->relationship('topic', 'name')
                    ->preload()
                    ->label('Topic')
                    ->helperText('What a parent would search for. This is the FILTER.'),

                Forms\Components\Select::make('petal_id')
                    ->relationship('petal', 'name')
                    ->preload()
                    ->label('Petal')
                    ->helperText('What the story turns out to be about developmentally. This is the EYEBROW the site prints — a different question from the topic, on purpose.'),

                Forms\Components\Select::make('ageStages')
                    ->relationship('ageStages', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Age stages')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Format')->schema([
                Forms\Components\Select::make('media_kind')
                    ->label('This story is')
                    ->options(['written' => 'Written', 'video' => 'A video'])
                    ->default('written')
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('youtube_id')
                    ->label('YouTube video ID')
                    ->helperText('Just the ID, not the whole URL — the part after v=. Families send us a file and we publish it on the Avdhara channel.')
                    ->visible(fn (Forms\Get $get): bool => $get('media_kind') === 'video')
                    ->requiredIf('media_kind', 'video'),

                Forms\Components\Select::make('media_aspect')
                    ->label('Shape')
                    ->options(['4/5' => 'Portrait (4:5)', '16/9' => 'Landscape (16:9)'])
                    ->default('4/5')
                    ->helperText('4:5 is the house ratio — these are filmed on phones.')
                    ->visible(fn (Forms\Get $get): bool => $get('media_kind') === 'video'),

                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric()
                    ->label('Length (minutes)')
                    ->visible(fn (Forms\Get $get): bool => $get('media_kind') === 'video'),

                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->directory('stories')
                    ->disk('public')
                    ->label('Photograph')
                    ->helperText('Used on written stories, and in the reading dialog.')
                    ->visible(fn (Forms\Get $get): bool => $get('media_kind') === 'written'),

                Forms\Components\TextInput::make('image_alt')
                    ->label('Photograph description')
                    ->helperText('What it shows, for a reader who cannot see it.')
                    ->visible(fn (Forms\Get $get): bool => $get('media_kind') === 'written')
                    ->columnSpanFull(),
            ])->columns(3),

            Forms\Components\Section::make('Publishing')->schema([
                Forms\Components\Toggle::make('is_published')->label('Published'),
                Forms\Components\Toggle::make('is_featured')->label('Featured')->helperText('Appears in the carousel at the top of Parent Stories.'),
                Forms\Components\DateTimePicker::make('published_at')->seconds(false),
            ])->columns(3),

            /*
             * SEPARATE FROM "Featured" ON PURPOSE. That flag is the Parent Stories carousel; this
             * is the homepage band, and while the homepage read the same flag the two could not
             * differ — promoting a story on the hub promoted it on the homepage as a side effect,
             * and nobody could choose which one led.
             */
            Forms\Components\Section::make('On the homepage')
                ->description('The "From the Parent Circle" band. Five stories: one large with its video, four small beneath.')
                ->schema([
                    Forms\Components\Select::make('home_position')
                        ->label('Homepage slot')
                        ->options([
                            1 => '1 — the large one, with the video',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                            5 => '5',
                        ])
                        ->placeholder('Not on the homepage')
                        ->helperText('Slot 1 is the lead. Leave empty to keep a story off the homepage. Two stories in the same slot is not an error, but only one of them will be shown.')
                        ->native(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->limit(50)->weight('semibold')->wrap(),
                Tables\Columns\TextColumn::make('author_relation')->label('Family')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('media_kind')
                    ->label('Format')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'video' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'video' ? 'Video' : 'Written'),
                Tables\Columns\TextColumn::make('petal.name')->label('Petal')->badge()->color('warning')->toggleable(),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean(),
                Tables\Columns\TextColumn::make('home_position')
                    ->label('Home')
                    ->badge()
                    ->color(fn (?int $state): string => $state === 1 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => $state === 1 ? 'Lead' : (string) $state)
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('media_kind')->label('Format')->options(['written' => 'Written', 'video' => 'Video']),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
                Tables\Filters\Filter::make('on_home')
                    ->label('On the homepage')
                    ->query(fn ($query) => $query->whereNotNull('home_position')),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStories::route('/'),
            'create' => Pages\CreateStory::route('/create'),
            'edit' => Pages\EditStory::route('/{record}/edit'),
        ];
    }
}
