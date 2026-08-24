<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SocialLinkResource\Pages;
use App\Models\SocialLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Where Avdhara is, publicly.
 *
 * These four URLs used to be literals in the website's source, so a new channel or a changed handle
 * meant a developer and a deploy. Opening a TikTok account is now pasting a link into a field.
 *
 * BLANK IS THE OFF SWITCH, and it is the whole design. Every platform exists as a row from the
 * start — X and TikTok included, with no URL. A row with no URL is omitted from the website
 * entirely: no icon, no gap, nothing to explain. Clearing a URL is how a channel is retired, and it
 * takes effect on the next revalidation without anybody touching code.
 *
 * ROWS ARE NOT CREATED OR DELETED HERE, and that is deliberate rather than an oversight. The
 * website maps `platform` to an icon it has to already have; a row for a platform it cannot draw
 * would render a blank circle. Adding a genuinely new network is a two-line change on the site plus
 * a seeder entry — small, but it is a code change, and pretending otherwise here would produce a
 * silent failure rather than an obvious one.
 */
class SocialLinkResource extends Resource
{
    protected static ?string $model = SocialLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Social links';

    protected static ?string $modelLabel = 'social link';

    protected static ?int $navigationSort = 3;

    /** See the class note: the set of platforms is fixed by what the website can draw. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')
                ->helperText('What a person reads — the accessible name on the icon. Safe to rename; the icon does not depend on it.')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('url')
                ->label('Profile URL')
                ->helperText('Leave EMPTY to hide this platform from the website completely. That is how a channel is switched off — no icon appears at all.')
                ->url()
                ->maxLength(300)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sort_order')
                ->label('Order')
                ->helperText('Lowest first, in the footer and on the contact page.')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('label')->weight('bold')->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Profile URL')
                    /* The empty state is a normal, expected state here — not a warning. */
                    ->placeholder('Not in use — hidden from the site')
                    ->color(fn (?string $state): string => filled($state) ? 'gray' : 'warning')
                    ->limit(50)
                    ->url(fn (SocialLink $record): ?string => $record->url)
                    ->openUrlInNewTab(),

                Tables\Columns\IconColumn::make('url')
                    ->label('Live')
                    ->boolean()
                    ->getStateUsing(fn (SocialLink $record): bool => filled($record->url)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialLinks::route('/'),
            'edit' => Pages\EditSocialLink::route('/{record}/edit'),
        ];
    }
}
