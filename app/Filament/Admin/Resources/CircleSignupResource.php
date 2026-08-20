<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CircleSignupResource\Pages;
use App\Models\CircleSignup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Parents asking to join a Circle, or to reserve a place.
 *
 * THIS IS AN INBOX, NOT A CONTENT TYPE. Nobody creates a record here — a parent does, from the
 * site — so there is no create action and the fields are read-only apart from the ones you act
 * with. Everything arrives `pending`, because the invite to a WhatsApp group is sent BY HAND: that
 * is what the confirmation copy promises the parent, and it is what keeps a small held space held.
 * An invite link that went out automatically would also be forwardable to anybody.
 *
 * The badge in the sidebar counts what is waiting, because an inbox nobody is prompted to open is
 * an inbox that fills up.
 */
class CircleSignupResource extends Resource
{
    protected static ?string $model = CircleSignup::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Circle signups';

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
        /* Signups come from the website. Creating one here would fabricate a consent nobody gave. */
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('What they asked for')->schema([
                Forms\Components\TextInput::make('kind')->disabled(),
                Forms\Components\Select::make('circle_id')->relationship('circle', 'name')->disabled(),
                Forms\Components\Select::make('gathering_id')->relationship('gathering', 'title')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('The family')->schema([
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->disabled()
                    ->helperText('The number to send the group invite to. Stored exactly as they typed it.'),
                Forms\Components\Select::make('age_stage_id')->relationship('ageStage', 'name')->label('Child\'s age')->disabled(),
                Forms\Components\Textarea::make('note')->label('What they would like to bring')->disabled()->rows(2)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Where it has got to')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending — nobody has looked yet',
                        'approved' => 'Approved — ready to invite',
                        'invited' => 'Invited — link sent',
                        'declined' => 'Declined',
                    ])
                    ->required()
                    ->helperText('Move this as you go, so somebody else can see whether this family has been answered.'),

                Forms\Components\DateTimePicker::make('handled_at')->label('Handled at')->seconds(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Came in')->since()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('kind')->badge()->color(fn (string $state): string => $state === 'join' ? 'success' : 'primary'),
                Tables\Columns\TextColumn::make('circle.name')->label('Circle')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('gathering.title')->label('Gathering')->limit(28)->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('whatsapp')->label('WhatsApp')->copyable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'primary',
                        'invited' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'invited' => 'Invited', 'declined' => 'Declined',
                ])->default('pending'),
                Tables\Filters\SelectFilter::make('kind')->options(['join' => 'Join', 'reserve' => 'Reserve']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),
                Tables\Actions\Action::make('invited')
                    ->label('Mark invited')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->visible(fn (CircleSignup $record): bool => $record->status !== 'invited')
                    ->requiresConfirmation()
                    ->modalDescription('Only after you have actually sent the WhatsApp invite. This is a record of what happened, not the thing that sends it.')
                    ->action(fn (CircleSignup $record) => $record->update([
                        'status' => 'invited',
                        'handled_at' => now(),
                        'handled_by' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleSignups::route('/'),
            'edit' => Pages\EditCircleSignup::route('/{record}/edit'),
        ];
    }
}
