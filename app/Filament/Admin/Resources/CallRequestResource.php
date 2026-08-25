<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallRequestResource\Pages;
use App\Models\Lead;
use App\Models\LeadKind;
use App\Models\LeadNote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Somebody has asked to be rung, and nobody has rung them yet.
 *
 * THE SAME TABLE AS LEADS, LIFTED OUT OF IT. A call request is a `Lead` with `kind = booking`, and
 * it stays one — the attribution, the notes, the offer machinery are all shared and duplicating any
 * of it would be worse. What is not shared is the CLOCK. Every other lead kind waits on a reply;
 * this one waits on a person picking up a phone, and it goes stale in hours rather than days.
 *
 * Buried as the third tab of a four-tab inbox, that clock was invisible. A request made yesterday
 * evening looked exactly like one made last week, and both looked like a waitlist signup that
 * genuinely could wait. This is the whole reason it is its own entry in the navigation: the thing
 * you need to know about a call request is HOW LONG IT HAS BEEN SITTING, and that only reads if
 * they are all on one screen with nothing else in the way.
 *
 * THE TWENTY-FOUR HOUR LINE is not a service-level promise, it is a visual one. Anything unanswered
 * for longer than a day turns red and sorts to the top. See `isOverdue`.
 *
 * WHAT HAPPENED ON THE CALL is the second thing this screen exists for. "Handled" threw away the
 * one fact worth keeping — whether anybody actually picked up — so the outcome action asks that
 * first and branches: picked up moves to `completed` and asks what was agreed; no answer moves to
 * `no_show` and leaves the row open, because a family who did not pick up still needs ringing.
 */
class CallRequestResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    /**
     * ITS OWN GROUP, DELIBERATELY OUTSIDE THE INBOX.
     *
     * The inbox answers "what came in". This answers "who is waiting on a phone call", which is a
     * different job done at a different time of day by whoever is making calls. It sits directly
     * under the inbox so it is still the second thing on the page.
     */
    protected static ?string $navigationGroup = 'Calls';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Call requests';

    protected static ?string $modelLabel = 'call request';

    protected static ?string $pluralModelLabel = 'call requests';

    /** How long a request may sit before the screen starts shouting about it. */
    private const OVERDUE_HOURS = 24;

    /**
     * Only bookings, everywhere.
     *
     * ON THE QUERY, NOT ON A FILTER, so it holds for the table, the edit page, the global search
     * and the badge alike. A filter is a thing somebody can clear.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', LeadKind::Booking->value);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Call: '.Str::limit((string) ($record->name ?: $record->email), 60);
    }

    /** Open means new or scheduled — see `LeadKind::openStatuses`. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = static::getEloquentQuery()
            ->whereIn('status', LeadKind::Booking->openStatuses())
            ->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    /**
     * Red the moment ANY request is overdue, not merely when many are.
     *
     * One family who asked for a call two days ago is exactly the case this whole screen exists to
     * surface. A badge that only turns red at some threshold would hide it.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        if (static::getNavigationBadge() === null) {
            return null;
        }

        return static::overdueQuery()->exists() ? 'danger' : 'warning';
    }

    /** Unanswered for more than a day. */
    private static function overdueQuery(): Builder
    {
        return static::getEloquentQuery()
            ->where('status', 'new')
            ->where('created_at', '<', now()->subHours(self::OVERDUE_HOURS));
    }

    private static function isOverdue(Lead $record): bool
    {
        return $record->status === 'new'
            && $record->created_at?->lt(now()->subHours(self::OVERDUE_HOURS));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Who')->schema([
                Forms\Components\TextInput::make('name')->maxLength(120),
                Forms\Components\TextInput::make('email')->email()->required()->maxLength(190),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(40)
                    ->helperText('The number to ring. A call request without one has to be answered by email first.'),
                Forms\Components\Select::make('age_stage_id')
                    ->relationship('ageStage', 'name')
                    ->label('Child’s stage')
                    ->preload(),
            ])->columns(2),

            Forms\Components\Section::make('What they asked')->schema([
                Forms\Components\Textarea::make('message')->rows(4)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Where it has got to')->schema([
                Forms\Components\Select::make('status')
                    ->options(LeadKind::Booking->statuses())
                    ->required()
                    ->helperText('Use “Log the call” on the list instead where you can — it records what happened at the same time.'),
                Forms\Components\Placeholder::make('requested')
                    ->label('Requested')
                    ->content(fn (?Lead $record): string => $record?->created_at?->diffForHumans() ?? '—'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            /*
             * OLDEST FIRST, which is the opposite of every other inbox here and is the point. A
             * call list is worked from the top, and the top must be the person who has been waiting
             * longest — newest-first would put the freshest request in front of somebody who has
             * been waiting three days.
             */
            ->defaultSort('created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waiting')
                    ->sortable()
                    ->description(fn (Lead $r): string => $r->created_at?->format('D j M, H:i') ?? '')
                    ->formatStateUsing(fn (?Carbon $state): string => $state?->diffForHumans(syntax: true) ?? '—')
                    /* The clock is the whole screen. Red once it is over a day old. */
                    ->color(fn (Lead $r): string => self::isOverdue($r) ? 'danger' : 'gray')
                    ->weight(fn (Lead $r): string => self::isOverdue($r) ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (Lead $r): ?string => $r->email),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Number')
                    ->searchable()
                    ->placeholder('none given')
                    ->copyable(),

                Tables\Columns\TextColumn::make('ageStage.name')->label('Stage')->badge()->color('gray')->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Lead $r): string => $r->statusLabel())
                    ->color(fn (Lead $r): string => LeadKind::Booking->statusColour((string) $r->status)),

                Tables\Columns\TextColumn::make('notes_count')
                    ->counts('notes')
                    ->label('Contacts')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('overdue')
                    ->label('Waiting over '.self::OVERDUE_HOURS.' hours')
                    ->query(fn (Builder $q): Builder => $q
                        ->where('status', 'new')
                        ->where('created_at', '<', now()->subHours(self::OVERDUE_HOURS))),

                Tables\Filters\SelectFilter::make('status')->options(LeadKind::Booking->statuses()),
            ])
            ->actions([
                /*
                 * THE ONE ACTION THIS SCREEN IS FOR.
                 *
                 * It asks whether they picked up FIRST, because that answer changes what the rest
                 * of the form means and what the row does next. Picked up: the call happened, so it
                 * wants to know what was agreed and the request is finished. No answer: nothing has
                 * been resolved, so the row must stay in somebody's list — it moves to `no_show`,
                 * which reads on the table as "tried, missed" rather than "done".
                 */
                Tables\Actions\Action::make('logCall')
                    ->label('Log the call')
                    ->icon('heroicon-o-phone')
                    ->color('primary')
                    ->visible(fn (Lead $r): bool => in_array($r->status, ['new', 'scheduled'], true))
                    ->form([
                        Forms\Components\Radio::make('picked_up')
                            ->label('Did they pick up?')
                            ->boolean('Yes, we spoke', 'No answer')
                            ->required()
                            ->live(),

                        Forms\Components\Textarea::make('body')
                            ->label(fn (Forms\Get $get): string => $get('picked_up')
                                ? 'What was said, and what happens next'
                                : 'Anything worth noting before the next attempt')
                            ->rows(4)
                            ->required(fn (Forms\Get $get): bool => (bool) $get('picked_up'))
                            ->helperText(fn (Forms\Get $get): string => $get('picked_up')
                                ? 'The next person to speak to this family reads this first.'
                                : 'Optional. Left blank, this still records that an attempt was made.'),
                    ])
                    ->action(function (Lead $record, array $data): void {
                        $pickedUp = (bool) $data['picked_up'];

                        $record->recordContact(
                            $pickedUp ? 'called' : 'no_answer',
                            $data['body'] ?: 'Called — no answer.',
                            $pickedUp ? 'completed' : 'no_show',
                            auth()->id(),
                        );

                        Notification::make()
                            ->title($pickedUp ? 'Call logged' : 'No answer recorded')
                            ->body($pickedUp
                                ? 'Marked completed.'
                                : 'Still open — it stays on the list for another attempt.')
                            ->success()
                            ->send();
                    }),

                /*
                 * CONVERT, which is what a good call is FOR.
                 *
                 * A call that went well ends with a family who wants a place, and until now that
                 * meant somebody retyping their details into the waitlist. This changes the kind on
                 * the row instead: same person, same attribution, same history of contacts, now in
                 * a process that can hold an offer. The trail says it happened, so the call does not
                 * vanish from the record when the row leaves this screen.
                 */
                Tables\Actions\Action::make('convert')
                    ->label('Convert to waitlist')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Lead $r): bool => $r->status === 'completed')
                    ->requiresConfirmation()
                    ->modalDescription('This family moves onto the waitlist, keeping their details, their attribution and every note recorded here. They will leave this screen.')
                    ->form([
                        Forms\Components\Textarea::make('body')
                            ->label('What they agreed to')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data): void {
                        $record->notes()->create([
                            'user_id' => auth()->id(),
                            'contact' => 'note',
                            'body' => 'Converted from a call request to the waitlist. '.$data['body'],
                            'status_after' => LeadKind::Waitlist->initialStatus(),
                        ]);

                        $record->forceFill([
                            'kind' => LeadKind::Waitlist->value,
                            'status' => LeadKind::Waitlist->initialStatus(),
                            'handled_at' => null,
                        ])->save();

                        Notification::make()
                            ->title('Moved to the waitlist')
                            ->body('You will find them under Leads → Join waitlist.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateHeading('No calls requested yet')
            ->emptyStateDescription(LeadKind::Booking->source());
    }

    public static function getRelations(): array
    {
        /* THE SAME RELATION MANAGER LEADS USES. The notes belong to the lead, not to the screen
           looking at it, and a second copy would drift the first time one gained a column. */
        return [\App\Filament\Admin\Resources\LeadResource\RelationManagers\NotesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallRequests::route('/'),
            'edit' => Pages\EditCallRequest::route('/{record}/edit'),
        ];
    }

    /** Nobody creates a call request by hand. They arrive from the site. */
    public static function canCreate(): bool
    {
        return false;
    }
}
