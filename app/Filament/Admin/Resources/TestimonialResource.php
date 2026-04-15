<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->required()
                    ->live()
                    ->options([
                        'text' => 'Text',
                        'youtube' => 'YouTube',
                    ]),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tags')
                    ->helperText('Comma separated (e.g. parenting, calm)')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\Textarea::make('content')
                    ->rows(4)
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('type') === 'text'),
                Forms\Components\TextInput::make('video_url')
                    ->url()
                    ->required(fn (Get $get): bool => $get('type') === 'youtube')
                    ->visible(fn (Get $get): bool => $get('type') === 'youtube'),
                Forms\Components\FileUpload::make('thumbnail')
                    ->image()
                    ->directory('testimonials')
                    ->visibility('public')
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('type') === 'youtube'),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Status')
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
