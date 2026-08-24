<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriberResource\Pages;
use App\Models\Subscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * THE EMAIL LIST — everybody the weekly email goes to.
 *
 * ITS OWN GROUP, NOT AN INBOX TAB, and that is the point of this class. Subscribers used to be the
 * fifth tab of Enquiries, next to four things that all needed doing, adding to the same pending
 * count. Nobody handles a subscriber. There is nothing to do about one, ever, so every one of them
 * sat in that count as work that could never be finished and made the number meaningless.
 *
 * THE QUESTION THIS SCREEN ANSWERS IS DIFFERENT. An inbox answers "what is waiting for me". This
 * answers "who gets Thursday's email, and did last Thursday's arrive" — which is a list, a health
 * check and a send, not a queue. Hence the columns: status, when they joined, when we last wrote.
 *
 * NO CREATE BUTTON AND NO EDITABLE EMAIL. Consent is the whole asset here: everybody on this list
 * put themselves on it. A form that lets somebody type an address in is how a list stops being one
 * people asked to be on, and it is one keystroke away from a spam complaint against the domain the
 * rest of the site sends from.
 */
class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static ?string $recordTitleAttribute = 'email';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Subscriber: '.Str::limit((string) $record->email, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Email list';

    protected static ?string $navigationLabel = 'Subscribers';

    protected static ?string $modelLabel = 'subscriber';

    protected static ?int $navigationSort = 1;

    /**
     * THE BADGE IS THE LIST SIZE, NOT A BACKLOG — and it is the one number on this panel that is
     * allowed to climb.
     *
     * Everywhere else a rising badge means work piling up. Here it means the list is growing, which
     * is the only thing anybody wants from it. It counts DELIVERABLE addresses rather than rows, so
     * it says what Thursday's send will actually reach.
     */
    public static function getNavigationBadge(): ?string
    {
        $live = Subscriber::query()->deliverable()->count();

        return $live > 0 ? (string) $live : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            /*
             * THE ADDRESS IS NOT EDITABLE. Changing it would silently move somebody's consent onto
             * an address that never gave any — which is the one thing on this screen that could put
             * the sending domain at risk.
             */
            Forms\Components\TextInput::make('email')->disabled(),

            Forms\Components\TextInput::make('name')->maxLength(120),

            /*
             * EDITABLE, because this is where a bounce gets recorded by hand until something
             * automatic does it. `unsubscribed` is a person's decision and normally arrives through
             * the link in the email rather than from here.
             */
            Forms\Components\Select::make('status')
                ->options(Subscriber::STATUSES)
                ->required()
                ->native(false)
                ->helperText('Only subscribed addresses are written to. Bounced is not the same as unsubscribed: one is a rejected address, the other is somebody asking us to stop.'),

            Forms\Components\TextInput::make('source')->label('Signed up from')->disabled(),

            Forms\Components\DateTimePicker::make('subscribed_at')->disabled()->seconds(false),
            Forms\Components\DateTimePicker::make('last_sent_at')->label('Last written to')->disabled()->seconds(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('name')->searchable()->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Subscriber::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Subscriber::STATUS_SUBSCRIBED => 'success',
                        Subscriber::STATUS_BOUNCED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source')->label('From')->toggleable()->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')->label('Joined')->since()->sortable(),

                /*
                 * The health check. An address that has never been written to, or was last written
                 * to long before the others, is how a broken send is noticed at all — the weekly
                 * job failing quietly looks exactly like nothing happening.
                 */
                Tables\Columns\TextColumn::make('last_sent_at')
                    ->label('Last written to')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),

                /*
                 * UNSUBSCRIBING ON SOMEBODY'S BEHALF, for when they reply to the email asking to
                 * stop instead of using the link. It sets the state rather than deleting the row —
                 * see `Subscriber::unsubscribe` for why the record has to stay.
                 */
                Tables\Actions\Action::make('unsubscribe')
                    ->label('Unsubscribe')
                    ->icon('heroicon-o-hand-raised')
                    ->color('danger')
                    ->visible(fn (Subscriber $record): bool => $record->status === Subscriber::STATUS_SUBSCRIBED)
                    ->requiresConfirmation()
                    ->modalDescription('They stay on file as unsubscribed. That record is what stops a later signup or import quietly putting them back on the list.')
                    ->action(function (Subscriber $record): void {
                        $record->unsubscribe();

                        Notification::make()->title('Unsubscribed')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Nobody on the list yet')
            ->emptyStateDescription('Subscribers arrive here from the Subscribe box in the footer of the Journal, Circles and Stories pages. Thursday\'s email goes to everybody on this list.');
    }

    /** Consent is the asset: nobody is typed onto this list by hand. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscribers::route('/'),
            'edit' => Pages\EditSubscriber::route('/{record}/edit'),
        ];
    }
}
