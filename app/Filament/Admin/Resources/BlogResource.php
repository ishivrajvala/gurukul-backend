<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BlogResource\Pages;
use App\Models\Blog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Blogs';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Textarea::make('short_description')
                    ->label('Short Description')
                    ->helperText('1-2 line summary for preview')
                    ->rows(3)
                    ->nullable(),
                Forms\Components\FileUpload::make('featured_image')
                    ->image()
                    ->directory('blogs')
                    ->visibility('public')
                    ->imagePreviewHeight('150')
                    ->maxSize(2048)
                    ->helperText('Recommended: 1200x675 (16:9 ratio)')
                    ->nullable(),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
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
                            ->maxLength(255),
                    ])
                    ->nullable(),
                Forms\Components\Select::make('tags')
                    ->label('Tags')
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
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
                            ->maxLength(255),
                    ]),
                TiptapEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('draft'),
                Forms\Components\DateTimePicker::make('published_at')
                    ->nullable(),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured Blog')
                    ->default(false),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($record): string => $record->tags->pluck('name')->implode(', ')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('tags')
                    ->label('Tags')
                    ->relationship('tags', 'name')
                    ->multiple(),
            ])
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
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'tags']);
    }
}
