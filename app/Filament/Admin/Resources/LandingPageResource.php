<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

/**
 * Landing pages: a slug, SEO, and an ordered stack of sections.
 *
 * SIX SECTION TYPES, EACH ONE A COMPONENT THE SITE ALREADY HAS. This is deliberately not a
 * free-form page builder. The site's architecture forbids redefining a shared component locally —
 * `SectionHead` was independently defined nine times and the copies drifted apart — and a page
 * assembled from arbitrary blocks is a page the design system has never seen. Six types that are
 * known to look right beats twenty that might.
 *
 * NO COLOURS AND NO LAYOUT CHOICES. An editor writes words, picks a section type and orders them.
 * Surfaces alternate and the palette is locked in the frontend, which is how a six-colour system
 * stays six colours.
 *
 * THE SLUG IS CHECKED AGAINST THE SITE'S REAL ROUTES. A landing page at `/contact` would be
 * shadowed by the real page for ever — the frontend resolves a static route before a dynamic one —
 * and nothing would say why the page never appeared. `App\Support\SiteRoutes` is that list, shared
 * with the announcement CTA picker, and has to be kept in step with the site's `app/` directory.
 */
class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

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
        return ['title', 'slug'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Landing page: '.\Illuminate\Support\Str::limit((string) $record->title, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Content System';

    protected static ?string $navigationLabel = 'Landing pages';

    protected static ?string $modelLabel = 'landing page';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The page')->schema([
                Forms\Components\TextInput::make('title')
                    ->helperText('For this list only. The headline a visitor reads is the hero section\'s own.')
                    ->required()
                    ->maxLength(160),

                Forms\Components\TextInput::make('slug')
                    ->prefix('avdhara.com/')
                    ->helperText('Lowercase, words separated by hyphens. Changing it breaks every link already shared.')
                    ->required()
                    ->maxLength(120)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    /* The site owns these already; a page here would never be reachable. */
                    ->rule(fn (): object => Rule::notIn(LandingPage::reservedSlugs()))
                    ->validationMessages([
                        'not_in' => 'The site already has a page at that address. Choose another.',
                        'regex' => 'Lowercase letters, numbers and hyphens only.',
                    ]),

                Forms\Components\Toggle::make('is_published')->label('Published'),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Live from')
                    ->seconds(false)
                    ->default(now())
                    ->helperText('A date in the future keeps the page off the site until then, even when Published is ticked.'),
            ])->columns(2),

            /*
             * WHAT GOOGLE SHOWS. Same polymorphic table the Journal uses — that was the point of it
             * being polymorphic, and a landing page is the surface that needs it most, because it
             * exists to be found.
             */
            Forms\Components\Section::make('Search results')
                ->description('Optional. Left blank, the page title and the hero\'s paragraph are used.')
                ->relationship('seo')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Title')
                        ->helperText('Around 60 characters. Longer is not an error — Google cuts it, so put what matters first.')
                        ->maxLength(180)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('meta_description')
                        ->label('Description')
                        ->helperText('Around 155 characters, written for somebody deciding whether to open it.')
                        ->rows(3)
                        ->maxLength(400)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('og_image')
                        ->label('Share image')
                        ->helperText('Used when the link is shared. Blank uses the site default.')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])->collapsed(),

            Forms\Components\Section::make('Sections')
                ->description('The page, top to bottom. Drag to reorder.')
                ->schema([
                    Forms\Components\Repeater::make('sections')
                        ->relationship()
                        ->hiddenLabel()
                        ->orderColumn('position')
                        ->collapsible()
                        ->collapsed()
                        ->cloneable()
                        ->addActionLabel('Add a section')
                        ->itemLabel(fn (array $state): string => static::sectionLabel($state))
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options(\App\Models\LandingSection::TYPES)
                                ->required()
                                ->live()
                                ->native(false)
                                ->columnSpanFull(),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Show this section')
                                ->default(true),

                            /*
                             * ONE GROUP PER TYPE, each visible only for its own type. A single set
                             * of fields shared across six section types is a form where two thirds
                             * of what is on screen does not apply, and an editor cannot tell which
                             * third does.
                             */
                            ...static::heroFields(),
                            ...static::richTextFields(),
                            ...static::pointsFields(),
                            ...static::faqFields(),
                            ...static::ctaFields(),
                            ...static::formFields(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }

    /** What the collapsed repeater row says, so a stack of six sections is readable closed. */
    private static function sectionLabel(array $state): string
    {
        $type = $state['type'] ?? null;
        $name = $type ? (\App\Models\LandingSection::TYPES[$type] ?? $type) : 'New section';
        $name = strtok($name, '—');

        $data = $state['data'] ?? [];
        $title = is_array($data) ? ($data['title'] ?? $data['heading'] ?? null) : null;

        return trim($name).($title ? ' · '.$title : '');
    }

    /** @return array<Forms\Components\Component> */
    private static function heroFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.eyebrow')
                ->label('Eyebrow')
                ->helperText('The small uppercase line above the headline.')
                ->maxLength(80)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero'),

            Forms\Components\TextInput::make('data.title')
                ->label('Headline')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('data.body')
                ->label('Paragraph')
                ->rows(3)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('data.ctaLabel')
                ->label('Button text')
                ->maxLength(80)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero'),

            Forms\Components\TextInput::make('data.ctaHref')
                ->label('Button goes to')
                ->maxLength(300)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero'),

            Forms\Components\FileUpload::make('data.image')
                ->label('Picture')
                ->image()
                ->disk('public')
                ->directory('landing')
                ->maxSize(2048)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'hero')
                ->columnSpanFull(),
        ];
    }

    /** @return array<Forms\Components\Component> */
    private static function richTextFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.heading')
                ->label('Heading')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'rich_text')
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('data.html')
                ->label('Copy')
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo'])
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'rich_text')
                ->columnSpanFull(),
        ];
    }

    /** @return array<Forms\Components\Component> */
    private static function pointsFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.heading')
                ->label('Heading')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'points')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('data.items')
                ->label('Points')
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(120),
                    Forms\Components\Textarea::make('body')->rows(2)->maxLength(400),
                ])
                ->defaultItems(3)
                ->maxItems(6)
                ->helperText('Three or six read best in the grid. Four and five leave a gap on the last row.')
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'points')
                ->columnSpanFull(),
        ];
    }

    /** @return array<Forms\Components\Component> */
    private static function faqFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.heading')
                ->label('Heading')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'faq')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('data.items')
                ->label('Questions')
                ->schema([
                    Forms\Components\TextInput::make('question')->required()->maxLength(200),
                    Forms\Components\Textarea::make('answer')->required()->rows(3)->maxLength(1200),
                ])
                ->defaultItems(3)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'faq')
                ->columnSpanFull(),
        ];
    }

    /** @return array<Forms\Components\Component> */
    private static function ctaFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.title')
                ->label('Heading')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'cta_band')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('data.body')
                ->label('One line beneath it')
                ->rows(2)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'cta_band')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('data.ctaLabel')
                ->label('Button text')
                ->maxLength(80)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'cta_band'),

            Forms\Components\TextInput::make('data.ctaHref')
                ->label('Button goes to')
                ->maxLength(300)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'cta_band'),
        ];
    }

    /** @return array<Forms\Components\Component> */
    private static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.heading')
                ->label('Heading')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'form')
                ->columnSpanFull(),

            Forms\Components\Select::make('data.form')
                ->label('Which form')
                ->options([
                    'waitlist' => 'Join the waitlist',
                    'contact' => 'Contact us',
                ])
                ->default('waitlist')
                ->native(false)
                ->helperText('Submissions land in Inbox > Enquiries, filed under that kind.')
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'form')
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('slug')
                    ->prefix('/')
                    ->color('gray')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections'),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Live from')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('Not scheduled')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingPages::route('/'),
            'create' => Pages\CreateLandingPage::route('/create'),
            'edit' => Pages\EditLandingPage::route('/{record}/edit'),
        ];
    }
}
