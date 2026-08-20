<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HomeConcernResource\Pages;
use App\Models\HomeConcern;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The three cards under "You are not alone" — the parent concerns.
 *
 * WRITE THEM AS A PARENT WOULD SAY THEM. "Screens are taking over childhood." is the card working;
 * "Our screen-time programme" is the card failing. The band exists to let somebody recognise
 * themselves, and a card that opens by selling breaks the only thing it is for. The link at the
 * foot is where the answer lives.
 *
 * THREE BULLETS. Not two, not six. They are read at a glance in a three-across grid, and the fourth
 * one is where the card stops being recognisable and starts being a list of symptoms.
 *
 * COLOUR IS NOT YOURS TO PICK — first card indigo, second marigold, third green, by position.
 */
class HomeConcernResource extends Resource
{
    protected static ?string $model = HomeConcern::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Homepage concerns';

    protected static ?string $modelLabel = 'concern';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('eyebrow')
                ->helperText('The small line above the headline: "One Story", "Another Story".')
                ->required()
                ->maxLength(60),

            Forms\Components\Select::make('icon')
                ->options([
                    'people' => 'People',
                    'phone' => 'Phone',
                    'brain' => 'Brain',
                    'book' => 'Book',
                    'leaf' => 'Leaf',
                ])
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('title')
                ->helperText('One sentence, in the parent\'s voice. "They deserve more than schools give."')
                ->required()
                ->maxLength(160)
                ->columnSpanFull(),

            Forms\Components\Repeater::make('bullets')
                ->label('Points')
                ->simple(Forms\Components\TextInput::make('point')->required()->maxLength(200))
                ->helperText('Three. Short enough to read at a glance in a three-across grid.')
                ->defaultItems(3)
                ->maxItems(4)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('cta')
                ->label('Link text')
                ->helperText('What the reader would ask for: "Help me bring balance."')
                ->maxLength(120),

            Forms\Components\TextInput::make('cta_href')
                ->label('Link goes to')
                ->default('/development-pathways')
                ->maxLength(300),

            Forms\Components\FileUpload::make('image_path')
                ->label('Illustration')
                ->image()
                ->disk('public')
                ->directory('home/concerns')
                ->maxSize(2048)
                ->helperText('A standing scene. It is cropped to a wide band at the foot of the card, framed on the upper body, so keep the figures centred.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('image_alt')
                ->label('Image description')
                ->helperText('Leave it blank and the picture is treated as decoration, which is right here: the card already says everything in words.')
                ->maxLength(300)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_published')->label('Published')->default(true),

            Forms\Components\TextInput::make('position')
                ->helperText('Lowest first. Also picks the colour: 1 indigo, 2 marigold, 3 green.')
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
                Tables\Columns\ImageColumn::make('image_path')->label('')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('eyebrow')->color('gray'),
                Tables\Columns\TextColumn::make('title')->wrap()->searchable(),
                /*
                 * FROM THE RECORD, NOT THE STATE. A TextColumn on a JSON array does not hand the
                 * array to `formatStateUsing` — it flattens it and calls the closure once per
                 * element, with a string. Typing the argument `?array` is valid PHP that throws
                 * when the column draws, which is a 500 on the list page and nothing before it.
                 * The same shape of bug (`$state` typed wrongly) took this panel down once before.
                 */
                Tables\Columns\TextColumn::make('bullets')
                    ->label('Points')
                    ->getStateUsing(fn (HomeConcern $record): int => count($record->bullets ?? [])),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeConcerns::route('/'),
            'create' => Pages\CreateHomeConcern::route('/create'),
            'edit' => Pages\EditHomeConcern::route('/{record}/edit'),
        ];
    }
}
