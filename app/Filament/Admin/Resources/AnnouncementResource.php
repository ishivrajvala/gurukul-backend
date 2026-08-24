<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Support\SiteRoutes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Seasons — the marigold strip above the nav.
 *
 * CALLED SEASONS RATHER THAN "TOP STRIP" because that is what people put in it: Diwali, a summer
 * intake, a founding-families window. "Top strip" named the piece of furniture; this names the job,
 * and a menu that lists furniture is a menu you have to translate before you can use it.
 *
 * IT USED TO BE A DEFAULT ARGUMENT IN A REACT COMPONENT, so changing the one piece of copy on the
 * site whose whole value is being current meant a developer and a deploy.
 *
 * SCHEDULE IT RATHER THAN REMEMBERING TO SWITCH IT OFF. Give a seasonal strip an end date and it
 * removes itself; the failure mode of a manual switch is a site still advertising a season that
 * finished last month, and the person who notices is a visitor.
 *
 * THE LINK IS PICKED, NOT TYPED. Choose a page the site already has, a landing page from the CMS,
 * or an external address. A typed path is a broken link waiting for a typo, and it cannot follow a
 * landing page whose slug later changes — the relation can.
 *
 * WHAT YOU MAY NOT DO HERE: urgency and scarcity. The site's voice rules ban both, which is why the
 * standing strip says how many families are being welcomed rather than how few places remain. No
 * "only N left", no countdowns, no deadlines. The colours are not on offer either — marigold with
 * indigo text is locked, because white on marigold is unreadable.
 */
class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Content System';

    protected static ?string $navigationLabel = 'Seasons';

    protected static ?string $modelLabel = 'season';

    protected static ?string $pluralModelLabel = 'seasons';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The message')->schema([
                Forms\Components\TextInput::make('label')
                    ->helperText('For this list only, so two seasonal strips are tellable apart. Visitors never see it.')
                    ->required()
                    ->maxLength(120),

                Forms\Components\TextInput::make('message')
                    ->helperText('One sentence. It is truncated on narrow screens, so put what matters first. No urgency, no scarcity, no countdowns.')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('cta_label')
                    ->label('Link text')
                    ->helperText('e.g. "See what is included". Leave blank for a strip with no link.')
                    ->maxLength(80),

                Forms\Components\Select::make('theme')
                    ->label('Kind')
                    ->options(Announcement::THEMES)
                    ->default('news')
                    ->native(false)
                    ->required()
                    ->helperText('Picks the icon on the strip, and makes past strips findable — "every Diwali one we have run" is a filter rather than a scroll. It does not change the colours: marigold with indigo text is fixed.'),

                Forms\Components\Select::make('audience')
                    ->label('Show on')
                    ->options(Announcement::AUDIENCES)
                    ->default('all')
                    ->native(false)
                    ->required()
                    ->helperText('The website, the app, or both. A family already inside a paid programme should not be sold the thing they have bought.'),
            ])->columns(2),

            Forms\Components\Section::make('Where the link goes')
                ->schema([
                    Forms\Components\Radio::make('cta_type')
                        ->hiddenLabel()
                        ->options([
                            'route' => 'A page on the site',
                            'landing' => 'A landing page',
                            'url' => 'Somewhere else',
                        ])
                        ->default('route')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('cta_route')
                        ->label('Page')
                        ->options(SiteRoutes::PAGES)
                        ->searchable()
                        ->native(false)
                        ->required(fn (Forms\Get $get): bool => $get('cta_type') === 'route')
                        ->visible(fn (Forms\Get $get): bool => $get('cta_type') === 'route')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('landing_page_id')
                        ->label('Landing page')
                        ->relationship('landingPage', 'title')
                        ->searchable()
                        ->preload()
                        ->helperText('Follows the page: renaming its address moves this link with it.')
                        ->required(fn (Forms\Get $get): bool => $get('cta_type') === 'landing')
                        ->visible(fn (Forms\Get $get): bool => $get('cta_type') === 'landing')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('cta_url')
                        ->label('Address')
                        ->url()
                        ->helperText('A full address including https://. Only for somewhere outside this site.')
                        ->required(fn (Forms\Get $get): bool => $get('cta_type') === 'url')
                        ->visible(fn (Forms\Get $get): bool => $get('cta_type') === 'url')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Campaign')
                ->description('Name the campaign and the leads this strip produces become attributable to it. Leave it empty and they arrive as Direct — indistinguishable from somebody who typed the address in.')
                ->schema([
                    Forms\Components\TextInput::make('utm_campaign')
                        ->label('Campaign name')
                        ->maxLength(120)
                        ->placeholder('summer_parent_circle')
                        ->helperText('Appended to this strip as utm_campaign. Lowercase with underscores keeps the reports readable — GA4 treats Summer and summer as two campaigns.'),

                    Forms\Components\TextInput::make('utm_source')
                        ->label('Source')
                        ->maxLength(80)
                        ->placeholder('site')
                        ->helperText('Defaults to "site". Change it only to tell this strip apart from an ad pointing at the same page in the same campaign.'),
                ])->columns(2)->collapsed(),

            Forms\Components\Section::make('When it shows')
                ->description('Leave a date empty for no bound: no start means it is already running, no end means until you stop it.')
                ->schema([
                    Forms\Components\Toggle::make('is_published')->label('Published'),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('From')
                        ->seconds(false),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Until')
                        ->seconds(false)
                        ->after('starts_at')
                        ->helperText('The strip removes itself. Set this and nobody has to remember.'),

                    Forms\Components\TextInput::make('priority')
                        ->helperText('Only one strip shows. When two overlap, the higher number wins — which is how a seasonal strip temporarily replaces the standing one.')
                        ->numeric()
                        ->default(0),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('theme')
                    ->label('Kind')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtok(Announcement::THEMES[$state] ?? $state, '('))
                    ->color(fn (string $state): string => $state === 'offer' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Shown on')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => Announcement::AUDIENCES[$state] ?? $state),
                Tables\Columns\TextColumn::make('message')->wrap()->limit(70)->color('gray'),
                Tables\Columns\TextColumn::make('cta_href')
                    ->label('Links to')
                    ->getStateUsing(fn (Announcement $record): string => $record->ctaHref() ?? 'No link')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('window')
                    ->label('Shows')
                    ->getStateUsing(fn (Announcement $record): string => static::windowLabel($record))
                    ->badge()
                    ->color(fn (Announcement $record): string => static::isLiveNow($record) ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('priority')->label('Wins over')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('theme')->label('Kind')->options(Announcement::THEMES),
                Tables\Filters\SelectFilter::make('audience')->label('Shown on')->options(Announcement::AUDIENCES),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * "Showing now", "From 1 Jun", "Ended 3 Sep" — the answer to the only question this list is
     * opened to ask. A published flag alone cannot answer it once dates are involved.
     */
    private static function windowLabel(Announcement $record): string
    {
        if (! $record->is_published) {
            return 'Not published';
        }

        if ($record->ends_at && $record->ends_at->isPast()) {
            return 'Ended '.$record->ends_at->format('j M');
        }

        if ($record->starts_at && $record->starts_at->isFuture()) {
            return 'From '.$record->starts_at->format('j M');
        }

        return 'Showing now';
    }

    private static function isLiveNow(Announcement $record): bool
    {
        return static::windowLabel($record) === 'Showing now';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
