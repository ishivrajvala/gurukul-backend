<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeadResource\RelationManagers;

use App\Models\LeadNote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * WHAT HAPPENED WITH THIS PARENT.
 *
 * The status says where a lead has got to. It cannot say what was said — "Completed" reads the same
 * whether the call went well or the parent asked to be tried again in March, and the second is the
 * only version worth knowing before anybody rings them back.
 *
 * APPEND ONLY: no edit action and no delete. A trail you can revise is a trail you cannot trust,
 * and the one time it matters is the one time somebody is reconstructing what was actually said to
 * a family.
 */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'What happened';

    protected static ?string $modelLabel = 'note';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('contact')
                ->label('How')
                ->options(LeadNote::CONTACTS)
                ->default('called')
                ->required()
                ->native(false)
                ->helperText('Recorded rather than inferred from the text, so "we have tried three times" is a question the panel can answer.'),

            Forms\Components\Textarea::make('body')
                ->label('What was said')
                ->required()
                ->rows(4)
                ->columnSpanFull()
                ->placeholder('Asked about screen time for a 6-year-old. Timing is wrong until March — try again then.'),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()
                    ->tooltip(fn (LeadNote $record): string => $record->created_at->format('j M Y, H:i')),

                Tables\Columns\TextColumn::make('contact')
                    ->label('How')
                    ->badge()
                    ->formatStateUsing(fn (LeadNote $record): string => $record->contactLabel())
                    ->color(fn (string $state): string => $state === 'no_answer' ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('body')->label('What was said')->wrap(),

                /* Who, so the person to ask is on the row rather than in somebody's memory. */
                Tables\Columns\TextColumn::make('author.name')->label('By')->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('status_after')
                    ->label('Moved to')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, LeadNote $record): string => $state
                        ? ($record->lead?->kind?->statuses()[$state] ?? $state)
                        : '—')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add a note')
                    ->icon('heroicon-o-pencil-square')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([])
            ->emptyStateHeading('Nothing recorded yet')
            ->emptyStateDescription('Every call, email and message with this parent goes here. It is what the next person picks up before getting in touch.');
    }
}
