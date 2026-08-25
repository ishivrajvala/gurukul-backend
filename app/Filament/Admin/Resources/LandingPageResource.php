<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use App\Models\LandingSection;
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

    protected static ?int $navigationSort = 3;

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
                            /*
                             * COMPONENT, THEN LAYOUT. Two pickers, because they answer two
                             * questions: what is this section for, and how should it look. The
                             * layout list is filtered by the component, so an editor is only ever
                             * offered arrangements that component is designed to survive.
                             */
                            Forms\Components\Select::make('component')
                                ->label('Section')
                                ->options(LandingSection::componentOptions())
                                ->required()
                                ->live()
                                ->native(false)
                                ->searchable()
                                ->helperText(fn (Forms\Get $get): ?string => LandingSection::purposeOf($get('component')))
                                /* Changing the component almost always invalidates the layout — a
                                   Hero's variants and an FAQ's barely overlap. Resetting to the new
                                   component's default is the only safe move; leaving the old value
                                   would store a layout the renderer has to silently correct. */
                                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('variant', LandingSection::defaultVariant($state)))
                                ->columnSpan(1),

                            Forms\Components\Select::make('variant')
                                ->label('Layout')
                                ->options(fn (Forms\Get $get): array => LandingSection::variantOptions($get('component')))
                                ->required()
                                ->live()
                                ->native(false)
                                ->helperText('How this section is arranged. The content below adapts to it.')
                                ->columnSpan(1),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Show this section')
                                ->default(true)
                                ->columnSpanFull(),

                            /*
                             * ONE SHARED FIELD SET, SHOWN BY LAYOUT — which is the whole payoff of
                             * every component sharing one data shape.
                             *
                             * The old form carried one group of fields per section type, and with
                             * six types that was already a screen where two thirds of what showed
                             * did not apply. At twenty-nine it would be unusable. Here a field
                             * appears when the CHOSEN LAYOUT actually reads it: `usesImage` and the
                             * helpers beside it hold the same knowledge the renderer has, written
                             * once.
                             *
                             * Because the shape is shared, switching a section from Cards to Image
                             * Right keeps everything already written in it, instead of moving it to
                             * a differently-named field and losing it.
                             */
                            ...static::contentFields(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }

    /** What the collapsed repeater row says, so a long page is readable closed. */
    private static function sectionLabel(array $state): string
    {
        $component = $state['component'] ?? null;
        $name = $component
            ? (LandingSection::COMPONENTS[$component]['label'] ?? $component)
            : 'New section';

        $variant = $state['variant'] ?? null;
        $layout = $variant ? (LandingSection::VARIANTS[$variant] ?? null) : null;

        $data = $state['data'] ?? [];
        $title = is_array($data) ? ($data['title'] ?? null) : null;

        /* Name · Title · Layout. The title is what somebody scanning a collapsed stack is looking
           for, so it comes before the layout rather than after the component name alone. */
        return trim($name)
            .($title ? ' · '.$title : '')
            .($layout ? '  ('.$layout.')' : '');
    }

    /* ---------------------------------------------------------------- fields -- */

    /** Layouts that draw the single picture. */
    private static function usesImage(?string $variant): bool
    {
        return in_array($variant, ['image-only', 'image-left', 'image-right', 'image-points', 'full-bleed', 'overlay'], true);
    }

    /** Layouts that draw the repeated list — points, cards, steps, questions. */
    private static function usesItems(?string $variant): bool
    {
        return in_array($variant, ['content-points', 'image-points', 'cards', 'grid', 'carousel', 'timeline', 'interactive', 'content-only'], true);
    }

    /** Layouts with words in them at all. A bare picture has none. */
    private static function usesText(?string $variant): bool
    {
        return ! in_array($variant, ['image-only', 'full-bleed'], true);
    }

    /** @return array<Forms\Components\Component> */
    private static function contentFields(): array
    {
        return [
            Forms\Components\TextInput::make('data.eyebrow')
                ->label('Eyebrow')
                ->helperText('The small uppercase line above the heading.')
                ->maxLength(80)
                ->visible(fn (Forms\Get $get): bool => static::usesText($get('variant')))
                ->columnSpan(1),

            Forms\Components\TextInput::make('data.title')
                ->label('Heading')
                ->maxLength(160)
                ->visible(fn (Forms\Get $get): bool => static::usesText($get('variant')))
                ->columnSpan(1),

            Forms\Components\Textarea::make('data.body')
                ->label('Lead paragraph')
                ->helperText('One or two plain sentences. For anything longer or formatted, use the rich text below.')
                ->rows(3)
                ->visible(fn (Forms\Get $get): bool => static::usesText($get('variant')))
                ->columnSpanFull(),

            /*
             * RICH TEXT ONLY WHERE IT IS ACTUALLY RENDERED. The layouts prefer `html` over `body`
             * when both exist, and only the prose-shaped ones read it — offering the editor on a
             * Cards section would let somebody write three paragraphs that never appear anywhere.
             */
            Forms\Components\RichEditor::make('data.html')
                ->label('Rich text')
                ->helperText('Used instead of the lead paragraph when filled in.')
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo'])
                ->visible(fn (Forms\Get $get): bool => in_array($get('variant'), ['content-only', 'full-width', 'image-left', 'image-right'], true))
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('data.image')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('landing')
                ->imageEditor()
                ->visible(fn (Forms\Get $get): bool => static::usesImage($get('variant')))
                ->columnSpan(1),

            Forms\Components\TextInput::make('data.imageAlt')
                ->label('Image description')
                ->helperText('For screen readers. Leave empty if the picture is purely decorative.')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => static::usesImage($get('variant')))
                ->columnSpan(1),

            Forms\Components\TextInput::make('data.video')
                ->label('YouTube id')
                ->helperText('Just the id, e.g. dQw4w9WgXcQ — not the whole address.')
                ->maxLength(40)
                ->visible(fn (Forms\Get $get): bool => $get('variant') === 'video')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('data.media')
                ->label('Gallery')
                ->schema([
                    Forms\Components\FileUpload::make('src')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('landing')
                        ->required(),
                    Forms\Components\TextInput::make('alt')->label('Description')->maxLength(200),
                ])
                ->addActionLabel('Add a picture')
                ->collapsed()
                ->itemLabel(fn (array $state): string => $state['alt'] ?? 'Picture')
                ->visible(fn (Forms\Get $get): bool => $get('variant') === 'gallery')
                ->columnSpanFull(),

            Forms\Components\Repeater::make('data.items')
                ->label(fn (Forms\Get $get): string => match ($get('variant')) {
                    'timeline' => 'Steps',
                    'content-only' => 'Questions',
                    default => 'Items',
                })
                ->schema([
                    Forms\Components\TextInput::make('meta')
                        ->label('Small label')
                        ->helperText('A step number, an age span, a duration. Optional.')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->maxLength(160),
                    Forms\Components\Textarea::make('body')
                        ->label('Text')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image')
                        ->label('Picture')
                        ->image()
                        ->disk('public')
                        ->directory('landing')
                        /* Only the layouts that actually draw a per-item picture. `../../` steps
                           out of the repeater item and back to the section. */
                        ->visible(fn (Forms\Get $get): bool => in_array($get('../../variant'), ['cards', 'grid', 'carousel', 'timeline'], true)),
                ])
                ->columns(2)
                ->addActionLabel('Add one')
                ->collapsed()
                ->itemLabel(fn (array $state): string => $state['title'] ?? 'Item')
                ->visible(fn (Forms\Get $get): bool => static::usesItems($get('variant')))
                ->columnSpanFull(),

            Forms\Components\TextInput::make('data.ctaLabel')
                ->label('Button text')
                ->maxLength(60)
                ->visible(fn (Forms\Get $get): bool => static::usesText($get('variant')))
                ->columnSpan(1),

            Forms\Components\TextInput::make('data.ctaHref')
                ->label('Button link')
                ->helperText('A path on this site, e.g. /begin')
                ->maxLength(200)
                ->visible(fn (Forms\Get $get): bool => static::usesText($get('variant')))
                ->columnSpan(1),

            /*
             * THE FORM BELONGS TO THE CALL TO ACTION ALONE. Both forms are the site's own — they
             * already post with their own kind, show their in-flight and failed states, and refuse
             * to show a thank-you for a submission that did not arrive. Offering one on any other
             * section would put two forms on a page competing for the same answer.
             */
            Forms\Components\Select::make('data.form')
                ->label('Attach a form')
                ->options(['waitlist' => 'Join the waitlist', 'contact' => 'Contact'])
                ->placeholder('No form')
                ->native(false)
                ->helperText('Replaces the button with the real form.')
                ->visible(fn (Forms\Get $get): bool => $get('component') === 'cta')
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
