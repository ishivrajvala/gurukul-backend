<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleQuestionResource\Pages;
use App\Models\CircleQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Questions parents have brought to the Circles.
 *
 * ANONYMOUS MEANS ANONYMOUS TO OTHER PARENTS, NOT TO YOU. A parent who ticked it was told exactly
 * that — the hint under the control says a moderator still sees it — so the name is visible here
 * and must NOT be relayed into the group. Relaying a question means retyping it without the
 * identity, which is a person's job and deliberately not a button.
 *
 * These arrive from parents asking about neurodivergence, developmental worry, family difficulty,
 * illness and grief. The anonymity is what makes those askable at all, so treating an anonymous row
 * carelessly costs more than any other record in this panel.
 *
 * NOTHING IS PUBLISHED AUTOMATICALLY, which is what the form promised.
 */
class CircleQuestionResource extends Resource
{
    protected static ?string $model = CircleQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Circle questions';

    public static function getNavigationBadge(): ?string
    {
        $waiting = static::getModel()::where('status', 'pending')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('anonymity')
                ->label('')
                ->content(fn (?CircleQuestion $record): string => $record?->is_anonymous
                    ? 'ASKED ANONYMOUSLY. The name below is visible to moderators only — the parent was told other parents would not see it. If this is relayed into a Circle, retype it without anything identifying.'
                    : 'Asked openly. The parent is happy to be named in the Circle.')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('question')->disabled()->rows(5)->columnSpanFull(),

            Forms\Components\Select::make('circle_id')->relationship('circle', 'name')->disabled(),
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending — not yet read',
                    'published' => 'Relayed into the Circle',
                    'answered' => 'Answered',
                    'declined' => 'Not taken forward',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('handled_at')->seconds(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Came in')->since()->sortable(),
                Tables\Columns\TextColumn::make('question')->limit(70)->wrap()->searchable(),
                Tables\Columns\IconColumn::make('is_anonymous')
                    ->label('Anonymous')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-user')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('circle.name')->label('Circle')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'pending' ? 'warning' : ($state === 'declined' ? 'gray' : 'success')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'published' => 'Relayed', 'answered' => 'Answered', 'declined' => 'Not taken forward',
                ])->default('pending'),
                Tables\Filters\TernaryFilter::make('is_anonymous')->label('Anonymous'),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Open')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleQuestions::route('/'),
            'edit' => Pages\EditCircleQuestion::route('/{record}/edit'),
        ];
    }
}
