<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CampaignResource\Pages;
use App\Filament\Admin\Resources\CampaignResource\RelationManagers\RecipientsRelationManager;
use App\Models\Campaign;
use App\Support\CampaignDispatcher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * WRITING AN EMAIL, LOOKING AT IT, AND SENDING IT TO THE LIST.
 *
 * The email list existed with no way to write to it except a scheduled command that assembles the
 * week's articles by itself. That covers the weekly note and nothing else — no way to send anything
 * one-off, no way to see what an email would look like before it went, and no way to schedule one.
 *
 * THE PREVIEW RENDERS THE REAL EMAIL. `Campaign::renderHtml()` is the same view the mailable uses,
 * so what this screen shows is the message that arrives rather than an approximation of it. It goes
 * into an IFRAME on purpose: an email is a full HTML document with its own <body> and table layout,
 * and dropping that into the panel's DOM would inherit the panel's stylesheet and show something
 * that renders nowhere.
 */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Email list';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $modelLabel = 'campaign';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'subject';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['subject'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Campaign: '.Str::limit((string) $record->subject, 60);
    }

    /**
     * Anything scheduled and not yet gone. Not a backlog — a "this is about to happen" count, which
     * is the one thing worth knowing about a campaign you did not write yourself.
     */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Campaign::where('status', Campaign::STATUS_SCHEDULED)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The email')
                ->description('Written once, sent to everybody on the list. Nothing is sent until you press Send or set a time.')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull()
                        ->helperText('The one line that decides whether this gets opened at all.'),

                    Forms\Components\TextInput::make('preheader')
                        ->label('Preview line')
                        ->maxLength(200)
                        ->columnSpanFull()
                        ->helperText('The grey line most mail clients show after the subject. Left empty, they fill it with the first words of the email instead — which is rarely what you would have chosen.'),

                    /*
                     * NO COLOUR TOOL IN THE TOOLBAR. The site's palette is six locked tokens, and an
                     * editor able to type a hex into an email writes it as an inline style that no
                     * stylesheet can correct once it has been delivered. The same reason the article
                     * editor's colour picker was removed — see `config/filament-tiptap-editor.php`.
                     */
                    Forms\Components\RichEditor::make('content')
                        ->label('Body')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'link', 'bulletList', 'orderedList',
                            'h2', 'h3', 'blockquote', 'undo', 'redo',
                        ])
                        ->columnSpanFull()
                        ->helperText('The brand shell — logo, footer, unsubscribe link — is added around this automatically. Write only the message.'),
                ]),

            Forms\Components\Section::make('Sending')
                ->schema([
                    Forms\Components\Placeholder::make('status_display')
                        ->label('Status')
                        ->content(fn (?Campaign $record): string => $record
                            ? (Campaign::STATUSES[$record->status] ?? $record->status)
                            : 'Draft'),

                    Forms\Components\Placeholder::make('audience')
                        ->label('Goes to')
                        ->content(fn (?Campaign $record): string => trans_choice(
                            '{0} nobody — the list is empty|{1} :count subscribed address|[2,*] :count subscribed addresses',
                            $record?->audienceSize() ?? 0,
                            ['count' => $record?->audienceSize() ?? 0],
                        )),

                    /*
                     * SCHEDULING IS JUST A TIME PLUS A STATUS. `campaigns:dispatch` runs every
                     * minute and picks up anything due; there is no second concept of a "scheduled
                     * job" that could drift out of step with what this field says.
                     */
                    Forms\Components\DateTimePicker::make('scheduled_for')
                        ->label('Send automatically at')
                        ->seconds(false)
                        ->minDate(now())
                        ->helperText('Leave empty to send by hand. Set a time and it goes out then, whether anybody is looking or not.')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Forms\Set $set, ?Campaign $record): void {
                            /* Setting a time schedules it; clearing one puts it back to a draft. */
                            if ($record && ! $record->isSendable()) {
                                return;
                            }

                            $set('status', $state ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_DRAFT);
                        }),

                    Forms\Components\Hidden::make('status')->default(Campaign::STATUS_DRAFT),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(48)->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Campaign::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Campaign::STATUS_SENT => 'success',
                        Campaign::STATUS_SENDING => 'warning',
                        Campaign::STATUS_SCHEDULED => 'primary',
                        Campaign::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                /*
                 * SENT AND FAILED SIDE BY SIDE. A campaign that reached 900 of 1,000 is not a
                 * success with a footnote — the hundred are the story, and a single "sent" count
                 * hides them completely.
                 */
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Delivered')
                    ->formatStateUsing(fn (Campaign $record): string => $record->recipients_count > 0
                        ? $record->sent_count.' / '.$record->recipients_count
                        : '—')
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : '—')
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('scheduled_for')->label('Scheduled')->dateTime('j M Y, H:i')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')->label('Sent')->since()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(Campaign::STATUSES),
            ])
            ->actions([
                /*
                 * PREVIEW IS THE FIRST ACTION, deliberately. It is the one thing you want before
                 * every send and the whole reason this screen exists.
                 */
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->iconButton()
                    ->modalHeading(fn (Campaign $record): string => $record->subject)
                    ->modalDescription('Exactly what arrives — the same template the send uses.')
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Campaign $record): Htmlable => new HtmlString(
                        view('filament.admin.campaign-preview', ['html' => $record->renderHtml()])->render(),
                    )),

                Tables\Actions\EditAction::make()->label('Open'),

                /*
                 * SEND NOW, BEHIND A CONFIRMATION THAT SAYS THE NUMBER.
                 *
                 * "Are you sure?" is not a safeguard — everybody presses yes. Naming how many people
                 * are about to receive it, and that it cannot be taken back, is the only version of
                 * this dialog that ever stops anybody.
                 */
                Tables\Actions\Action::make('send')
                    ->label('Send now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->iconButton()
                    ->visible(fn (Campaign $record): bool => $record->isSendable())
                    ->requiresConfirmation()
                    ->modalHeading('Send this campaign?')
                    ->modalDescription(fn (Campaign $record): string => sprintf(
                        'It goes to %d subscribed address(es) immediately. An email cannot be recalled once it has left.',
                        $record->audienceSize(),
                    ))
                    ->modalSubmitActionLabel('Send it')
                    ->action(function (Campaign $record): void {
                        $count = CampaignDispatcher::dispatch($record);

                        if ($count === 0) {
                            Notification::make()
                                ->title('Nothing was sent')
                                ->body('Either the list is empty or this campaign was already being sent.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Sending to '.$count.' subscriber(s)')
                            ->body('It goes out over the queue. The delivered count fills in as it lands.')
                            ->success()
                            ->send();
                    }),

                /*
                 * DUPLICATE, because most campaigns are the last one with different words, and the
                 * alternative is retyping the body into a new draft.
                 */
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->iconButton()
                    ->action(function (Campaign $record): void {
                        $copy = $record->replicate(['status', 'scheduled_for', 'sent_at', 'recipients_count', 'sent_count', 'failed_count']);
                        $copy->subject = $record->subject.' (copy)';
                        $copy->status = Campaign::STATUS_DRAFT;
                        $copy->created_by = auth()->id();
                        $copy->save();

                        Notification::make()->title('Copied to a new draft')->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    /* A sent campaign is a record of something that happened. It stays. */
                    ->visible(fn (Campaign $record): bool => $record->status !== Campaign::STATUS_SENT),
            ])
            ->emptyStateHeading('No campaigns yet')
            ->emptyStateDescription('Write one, look at it, then send it to the email list or set a time for it to go on its own.');
    }

    public static function getRelations(): array
    {
        return [RecipientsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
