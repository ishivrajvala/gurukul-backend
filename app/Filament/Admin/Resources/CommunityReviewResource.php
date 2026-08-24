<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CommunityReviewResource\Pages;
use App\Models\CommunityReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Screenshots of what parents said elsewhere.
 *
 * PERMISSION IS THE WHOLE POINT OF THIS SCREEN. Google, Instagram and YouTube comments are public;
 * WhatsApp messages and emails are PRIVATE, and republishing one without the family agreeing is a
 * breach whatever the message says. The site reads `publishable()` and nothing else, so a row
 * without permission simply never appears — the toggle is not advisory.
 *
 * That is why the table leads with it and defaults to showing what is still waiting: the useful
 * question about a wall of screenshots is which ones you are not yet allowed to use.
 *
 * NO RATING AND NO TOTAL, here or anywhere. Avdhara's trust model runs on real family voices rather
 * than social-proof numbers, and an average is the moment a wall of individual voices turns into a
 * marketing statistic.
 */
class CommunityReviewResource extends Resource
{
    protected static ?string $model = CommunityReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Parenting';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Community reviews';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('source')
                ->options([
                    'google' => 'Google',
                    'instagram' => 'Instagram',
                    'youtube' => 'YouTube',
                    'whatsapp' => 'WhatsApp',
                    'email' => 'Email',
                ])
                ->required()
                ->live()
                ->helperText('Shown as a badge on the screenshot. A screenshot with no provenance is just an image with words on it.'),

            Forms\Components\TextInput::make('position')->numeric()->default(0),

            Forms\Components\FileUpload::make('image_path')
                ->image()
/*
 * EXPLICIT TYPES AND A CEILING. `->image()` alone accepts `image/*`, which is
 * the BROWSER's word for what a file is — trivially set to anything by a
 * client that is not a browser. Naming the formats makes Laravel check the
 * real mime type server-side, and the size cap stops an upload field being a
 * way to fill the disk.
 *
 * SVG IS DELIBERATELY ABSENT. It is a document, not a picture: it can carry
 * script, and served from our own origin that script would run as us.
 */
->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
->maxSize(4096)
                ->directory('stories/reviews')
                ->disk('public')
                ->required()
                ->imageEditor()
                ->helperText('Crop out phone numbers, profile photos and surnames before uploading.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('image_alt')
                ->label('Description')
                ->required()
                ->helperText('What the screenshot says, for a reader who cannot see it. A wall of images with no alt text is a wall of nothing to a screen reader.')
                ->columnSpanFull(),

            Forms\Components\Toggle::make('has_permission')
                ->label('The family has given permission')
                ->helperText(fn (Forms\Get $get): string => in_array($get('source'), ['whatsapp', 'email'], true)
                    ? 'This came from a PRIVATE message. It cannot be published without the family agreeing in writing, and the site will not show it until this is on.'
                    : 'Public comments still get asked. The site will not show this until it is on.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('image_width')->numeric()->label('Width (px)'),
            Forms\Components\TextInput::make('image_height')->numeric()->label('Height (px)'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('')->height(56),
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('image_alt')->label('Description')->limit(60)->wrap(),
                Tables\Columns\IconColumn::make('has_permission')
                    ->label('Permission')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\Filter::make('awaiting_permission')
                    ->label('Awaiting permission')
                    ->query(fn ($query) => $query->where('has_permission', false)),
                Tables\Filters\SelectFilter::make('source')->options([
                    'google' => 'Google', 'instagram' => 'Instagram', 'youtube' => 'YouTube',
                    'whatsapp' => 'WhatsApp', 'email' => 'Email',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunityReviews::route('/'),
            'create' => Pages\CreateCommunityReview::route('/create'),
            'edit' => Pages\EditCommunityReview::route('/{record}/edit'),
        ];
    }
}
