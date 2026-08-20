<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The short reflections. NOT stories.
 *
 * A story is a narrative with a page of its own; a testimonial is a sentence with nowhere further
 * to go. They are two resources because collapsing them gives you either testimonials padded out to
 * look like articles or stories flattened into pull quotes.
 *
 * ATTRIBUTION IS THE PATHWAY FAMILY — "Explorer family" — and there is deliberately no name field
 * on this form and no photograph. These are the least-contextualised things on the page, so they
 * carry the least identifying detail: a sentence with a stranger's face attached invites a reader
 * to weigh the person rather than hear the words.
 */
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Stories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('content')
                ->label('The quote')
                ->required()
                ->rows(3)
                ->maxLength(300)
                ->helperText('One or two sentences, as the parent said them. The site adds the quotation marks.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('family_label')
                ->label('Family')
                ->required()
                ->placeholder('Explorer family')
                ->helperText('The pathway family, never a name. Seeker, Explorer, Builder, Thinker, Leader or Visionary.'),

            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('position')->numeric()->default(0),

            Forms\Components\Toggle::make('is_published')->label('Published')->default(true),

            /* NOT NULL from when testimonials were a generic marketing block, and every one of
               these is a written line — the filmed ones are Stories. Hidden rather than asked. */
            Forms\Components\Hidden::make('type')->default('text'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('content')->label('Quote')->limit(80)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('family_label')->label('Family')->badge()->color('gray'),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_published')->label('Published')])
            ->actions([Tables\Actions\EditAction::make()]);
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
