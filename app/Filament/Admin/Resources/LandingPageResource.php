<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Str;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Landing Pages';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('draft'),
                Forms\Components\DateTimePicker::make('published_at')
                    ->nullable(),
                Forms\Components\Repeater::make('sections')
                    ->relationship()
                    ->orderColumn('order')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->live()
                            ->options([
                                'hero' => 'Hero',
                                'text_only' => 'Text Only',
                                'image_left_text' => 'Image Left + Text',
                                'image_right_text' => 'Image Right + Text',
                                'image_only' => 'Image Only',
                                'features' => 'Features / Points',
                            ])
                            ->afterStateUpdated(function (Set $set): void {
                                $set('data', []);
                            }),
                        Forms\Components\TextInput::make('order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('data.title')
                                ->required(fn (Get $get): bool => $get('type') === 'hero'),
                            Forms\Components\Textarea::make('data.description')
                                ->required(fn (Get $get): bool => $get('type') === 'hero')
                                ->rows(3),
                            Forms\Components\TextInput::make('data.cta_text'),
                            Forms\Components\TextInput::make('data.cta_link'),
                            Forms\Components\FileUpload::make('data.background_image')
                                ->image()
                                ->directory('landing')
                                ->visibility('public')
                                ->helperText('Recommended: 1920x1080'),
                        ])->visible(fn (Get $get): bool => $get('type') === 'hero'),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('data.title')
                                ->required(fn (Get $get): bool => $get('type') === 'text_only'),
                            TiptapEditor::make('data.content')
                                ->required(fn (Get $get): bool => $get('type') === 'text_only')
                                ->columnSpanFull(),
                        ])->visible(fn (Get $get): bool => $get('type') === 'text_only'),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('data.title')
                                ->required(fn (Get $get): bool => in_array($get('type'), ['image_left_text', 'image_right_text'], true)),
                            TiptapEditor::make('data.content')
                                ->required(fn (Get $get): bool => in_array($get('type'), ['image_left_text', 'image_right_text'], true))
                                ->columnSpanFull(),
                            Forms\Components\FileUpload::make('data.image')
                                ->image()
                                ->directory('landing-pages')
                                ->visibility('public')
                                ->required(fn (Get $get): bool => in_array($get('type'), ['image_left_text', 'image_right_text'], true)),
                        ])->visible(fn (Get $get): bool => in_array($get('type'), ['image_left_text', 'image_right_text'], true)),
                        Forms\Components\Group::make([
                            Forms\Components\FileUpload::make('data.image')
                                ->image()
                                ->directory('landing-pages')
                                ->visibility('public')
                                ->required(fn (Get $get): bool => $get('type') === 'image_only'),
                        ])->visible(fn (Get $get): bool => $get('type') === 'image_only'),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('data.title')
                                ->required(fn (Get $get): bool => $get('type') === 'features'),
                            Forms\Components\Repeater::make('data.points')
                                ->schema([
                                    Forms\Components\TextInput::make('text')
                                        ->required(),
                                ])
                                ->defaultItems(1)
                                ->reorderableWithButtons(),
                        ])->visible(fn (Get $get): bool => $get('type') === 'features'),
                    ])
                    ->columnSpanFull(),
                Forms\Components\Section::make('SEO Settings')
                    ->relationship('seoMeta')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->nullable(),
                        Forms\Components\Textarea::make('meta_description')
                            ->nullable()
                            ->rows(3),
                        Forms\Components\FileUpload::make('og_image')
                            ->image()
                            ->directory('seo')
                            ->visibility('public')
                            ->nullable(),
                        Forms\Components\TextInput::make('canonical_url')
                            ->nullable(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
