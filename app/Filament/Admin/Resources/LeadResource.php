<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeadResource\Pages;
use App\Filament\Admin\Resources\LeadResource\RelationManagers\NotesRelationManager;
use App\Models\Lead;
use App\Models\LeadKind;
use App\Models\LeadNote;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * LEADS — the four site forms where somebody is waiting on a reply.
 *
 * Join waitlist · Contact · Book a call · Parent guide.
 *
 * ONE RESOURCE, because four near-identical screens is four places to forget to look. `kind` is
 * the tab, and the extra fields each form collected are in `payload` rather than four sets of
 * columns nobody else uses.
 *
 * FOUR PROCESSES, NOT ONE. This screen used to offer every row the same three statuses —
 * `pending / handled / declined` — which is the only vocabulary four different processes could
 * agree on and describes none of them. The status dropdown, the badge colours, the row action and
 * the counts are now all driven by `LeadKind`, so what a booking may become and what a contact
 * message may become are different lists and neither is written down here.
 *
 * NEWSLETTER IS NO LONGER ONE OF THESE. Nobody handles a subscriber; see `SubscriberResource`.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $recordTitleAttribute = 'email';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Lead: '.Str::limit((string) $record->email, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $modelLabel = 'lead';

    /**
     * OPEN, NOT PENDING. The badge counts what is still somebody's to chase, which per kind means
     * different statuses — an invited family and a scheduled call are both mid-process and both
     * still ours. Counting only untouched rows would hide every follow-up; counting everything
     * unfinished would climb for ever. `Lead::scopeOpen` draws that line once.
     */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Lead::query()->open()->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            /*
             * EVERYTHING THE PERSON SENT IS DISABLED. This is a record of what somebody typed into
             * a form; editing it would quietly rewrite their words, and the one time that matters
             * is the one time somebody is trying to work out what was actually said.
             */
            /*
             * `LeadKind|string|null`, and the union is not defensiveness.
             *
             * On a TABLE the state arrives already cast to the enum, because `Lead` casts `kind`.
             * On a FORM it arrives as the raw string — form state is an array Filament fills from
             * the record, and the cast does not survive the trip. A closure typed for only one of
             * the two 500s on whichever page it did not expect.
             */
            Forms\Components\TextInput::make('kind')
                ->label('Form')
                ->formatStateUsing(fn (LeadKind|string|null $state): string => self::coerce($state)?->label() ?? '—')
                ->disabled(),

            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),

            Forms\Components\Select::make('age_stage_id')
                ->relationship('ageStage', 'name')
                ->label('Child\'s age')
                ->disabled(),

            Forms\Components\Textarea::make('message')->disabled()->columnSpanFull(),

            Forms\Components\KeyValue::make('payload')
                ->label('Anything else the form collected')
                ->disabled()
                ->columnSpanFull(),

            /*
             * THE ONE EDITABLE FIELD, and its options come from the record's own kind.
             *
             * `$get('kind')` rather than `$record->kind`, because on a form the kind is state like
             * anything else — reading it off the record would go stale the moment Filament
             * refreshes the schema, and the dropdown would offer a booking's statuses on a contact
             * message.
             */
            Forms\Components\Select::make('status')
                ->label('Where this has got to')
                ->options(fn (Forms\Get $get): array => self::statusesFor($get('kind')))
                ->required()
                ->native(false)
                ->helperText(fn (Forms\Get $get): string => self::processHint($get('kind'))),

            Forms\Components\DateTimePicker::make('handled_at')
                ->label('Last acted on')
                ->seconds(false),

            /*
             * THE OFFER, WAITLIST ONLY — see `LeadKind::takesOffer`. A contact message has nothing
             * to move somebody toward yet, and an offer field on one is an invitation to discount
             * for no reason. Free text rather than a picker because there is no offer catalogue
             * yet; a code plus a date answers the question actually being asked, and becomes a
             * foreign key later without losing a row.
             */
            Forms\Components\TextInput::make('offer_code')
                ->label('Offer given')
                ->maxLength(40)
                ->placeholder('e.g. FOUNDING-SUMMER')
                ->visible(fn (Forms\Get $get): bool => self::coerce($get('kind'))?->takesOffer() ?? false)
                ->helperText('What they were offered to register with — an experience or inclusion, not a discount.')
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                    /* Stamped automatically: an offer date filled in by hand is filled in never. */
                    $set('offer_applied_at', $state ? now() : null);
                }),

            Forms\Components\DateTimePicker::make('offer_applied_at')
                ->label('Offer given on')
                ->seconds(false)
                ->visible(fn (Forms\Get $get): bool => self::coerce($get('kind'))?->takesOffer() ?? false),

            Forms\Components\DateTimePicker::make('registered_at')
                ->label('Registered on')
                ->seconds(false)
                ->visible(fn (Forms\Get $get): bool => self::coerce($get('kind'))?->takesOffer() ?? false)
                ->helperText('Set automatically when the lead reaches Registered.'),

            Forms\Components\Section::make('How they found us')
                ->description('What the browser sent with the form. Read-only — a hand-edited attribution reads as measurement and is worse than none.')
                ->schema(self::attributionFields())
                ->columns(2)
                ->collapsed()
                ->columnSpanFull(),
        ])->columns(2);
    }

    /**
     * WHERE THIS FAMILY CAME FROM. Entirely read-only — it is a record of what the browser sent,
     * and a hand-edited attribution is worse than none because it reads as measurement.
     *
     * Collapsed by default: it matters when reviewing a campaign and is in the way when working
     * through the inbox.
     *
     * @return array<Forms\Components\Component>
     */
    private static function attributionFields(): array
    {
        return [
            Forms\Components\TextInput::make('utm_source')->label('Source')->disabled(),
            Forms\Components\TextInput::make('utm_medium')->label('Medium')->disabled(),
            Forms\Components\TextInput::make('utm_campaign')->label('Campaign')->disabled(),
            /* The individual post or ad — the difference between "Instagram worked" and "that reel worked". */
            Forms\Components\TextInput::make('utm_content')->label('Ad or post')->disabled(),
            Forms\Components\TextInput::make('first_source')->label('First found us via')->disabled(),
            Forms\Components\TextInput::make('first_campaign')->label('First campaign')->disabled(),
            Forms\Components\TextInput::make('landing_path')->label('Arrived on')->disabled(),
            Forms\Components\TextInput::make('submitted_path')->label('Submitted from')->disabled(),
            Forms\Components\TextInput::make('referrer')->label('Referrer')->disabled()->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Came in')->since()->sortable(),

                Tables\Columns\TextColumn::make('kind')
                    ->label('Form')
                    ->badge()
                    ->formatStateUsing(fn (LeadKind|string|null $state): string => self::coerce($state)?->label() ?? '—'),

                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('message')->limit(50)->wrap()->toggleable(),

                /*
                 * The label and the colour both come from the record's kind, so "Scheduled" can
                 * appear on a booking and nowhere else without this column knowing what a booking
                 * is.
                 */
                /*
                 * HOW THEY FOUND US. Toggleable rather than always on: it is the column you want
                 * when reviewing a campaign and noise when working through the inbox.
                 */
                Tables\Columns\TextColumn::make('utm_source')
                    ->label('Came from')
                    ->formatStateUsing(fn (Lead $record): string => $record->sourceLabel())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Lead $record): string => $record->statusLabel())
                    ->color(fn (Lead $record): string => $record->kind?->statusColour($record->status) ?? 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->label('Form')
                    ->options(LeadKind::options()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadKind::allStatusOptions()),

                /*
                 * BUILT FROM WHAT IS ACTUALLY IN THE TABLE, not a hardcoded list. Campaign names
                 * are invented by whoever writes the link; a fixed dropdown would be out of date
                 * the first time somebody launches something.
                 */
                Tables\Filters\SelectFilter::make('utm_source')
                    ->label('Came from')
                    ->options(fn (): array => Lead::query()
                        ->whereNotNull('utm_source')
                        ->distinct()
                        ->orderBy('utm_source')
                        ->pluck('utm_source', 'utm_source')
                        ->all()),

                Tables\Filters\SelectFilter::make('utm_campaign')
                    ->label('Campaign')
                    ->options(fn (): array => Lead::query()
                        ->whereNotNull('utm_campaign')
                        ->distinct()
                        ->orderBy('utm_campaign')
                        ->pluck('utm_campaign', 'utm_campaign')
                        ->all()),

                /*
                 * DEFAULTS TO OPEN, which is what the old `status = pending` default was reaching
                 * for and could not express once each kind had its own words for it.
                 */
                Tables\Filters\TernaryFilter::make('open')
                    ->label('Still open')
                    ->placeholder('Everything')
                    ->trueLabel('Still open')
                    ->falseLabel('Finished with')
                    ->queries(
                        true: fn ($query) => $query->open(),
                        false: fn ($query) => $query->whereNotIn('id', Lead::query()->open()->select('id')),
                        blank: fn ($query) => $query,
                    )
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),

                /*
                 * LOG A CONTACT AND MOVE THE LEAD IN ONE ACT, because they are one act.
                 *
                 * Somebody who has just rung a family should not have to remember to also change a
                 * dropdown — and a status moved with no note is precisely the gap this closes. The
                 * status field defaults to where the lead already is, so recording a call that
                 * changed nothing is as easy as recording one that did.
                 */
                Tables\Actions\Action::make('logContact')
                    ->label('Log a call or note')
                    ->icon('heroicon-o-phone')
                    ->color('gray')
                    ->iconButton()
                    ->modalHeading(fn (Lead $record): string => 'What happened with '.($record->name ?: $record->email).'?')
                    ->modalSubmitActionLabel('Save it')
                    ->form(fn (Lead $record): array => [
                        Forms\Components\Select::make('contact')
                            ->label('How')
                            ->options(LeadNote::CONTACTS)
                            ->default('called')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('body')
                            ->label('What was said')
                            ->required()
                            ->rows(4)
                            ->placeholder('Timing is wrong until March — try again then.'),

                        Forms\Components\Select::make('status')
                            ->label('Where this has got to now')
                            ->options($record->statusOptions())
                            ->default($record->status)
                            ->required()
                            ->native(false)
                            ->helperText('Leave it where it is if the call changed nothing.'),
                    ])
                    ->action(function (Lead $record, array $data): void {
                        $record->recordContact(
                            $data['contact'],
                            $data['body'],
                            $data['status'],
                            auth()->id(),
                        );

                        Notification::make()->title('Recorded')->success()->send();
                    }),

                /*
                 * ONE CLICK ADVANCES ONE STEP, and only ever along the happy path: new → invited,
                 * new → scheduled, scheduled → completed. `LeadKind::nextStatus` refuses to hand
                 * back `declined`, `no_show` or `cancelled`, because marking a family as having
                 * declined by accident is not a mistake anybody finds out about.
                 */
                Tables\Actions\Action::make('advance')
                    ->label(fn (Lead $record): string => self::advanceLabel($record))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Lead $record): bool => $record->kind?->nextStatus($record->status) !== null)
                    ->requiresConfirmation()
                    ->action(function (Lead $record): void {
                        $next = $record->kind?->nextStatus($record->status);

                        if ($next === null) {
                            return;
                        }

                        /* `moveTo` so the Advance button stamps the same things every other path does. */
                        $record->moveTo($next, auth()->id());
                    }),
            ])
            ->emptyStateHeading(fn ($livewire): string => self::kindFor($livewire->activeTab ?? null)?->emptyHeading() ?? 'Nothing here yet')
            ->emptyStateDescription(fn ($livewire): string => self::kindFor($livewire->activeTab ?? null)?->source()
                ?? 'Waitlist signups, contact messages, call bookings and parent-guide requests all arrive here as people use the site.');
    }

    /** The next-step button's wording, so it names the step rather than saying "Advance". */
    private static function advanceLabel(Lead $record): string
    {
        $next = $record->kind?->nextStatus($record->status);

        return $next === null
            ? 'Advance'
            : 'Mark '.lcfirst($record->kind->statuses()[$next] ?? $next);
    }

    /** @param LeadKind|string|null $kind As it arrives from form state, which may be either. */
    private static function statusesFor(LeadKind|string|null $kind): array
    {
        return self::coerce($kind)?->statuses() ?? LeadKind::allStatusOptions();
    }

    private static function processHint(LeadKind|string|null $kind): string
    {
        $resolved = self::coerce($kind);

        return $resolved === null
            ? ''
            : 'The '.lcfirst($resolved->label()).' process: '.implode(' → ', array_values($resolved->statuses())).'.';
    }

    public static function coerce(LeadKind|string|null $kind): ?LeadKind
    {
        return $kind instanceof LeadKind ? $kind : (is_string($kind) ? LeadKind::tryFrom($kind) : null);
    }

    /** The active tab is a kind's value, or `all`. */
    private static function kindFor(?string $tab): ?LeadKind
    {
        return $tab === null ? null : LeadKind::tryFrom($tab);
    }

    /**
     * THE TRAIL. A status says where a lead has got to; the notes say what was actually said.
     */
    public static function getRelations(): array
    {
        return [NotesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
