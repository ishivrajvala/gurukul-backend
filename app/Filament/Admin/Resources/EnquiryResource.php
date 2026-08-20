<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EnquiryResource\Pages;
use App\Models\Enquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Everything else the site collects: waitlist, contact, newsletter, booking, parent guide, careers.
 *
 * ONE RESOURCE, because six near-identical screens is six places to forget to look. `kind` is the
 * filter, and the extra fields each form collected are in `payload` rather than as six sets of
 * columns nobody else uses.
 *
 * The three inboxes above are separate for a reason worth restating: each carries a rule the schema
 * has to hold — a WhatsApp number, an anonymity flag, a reproducible consent. Folding those into a
 * JSON blob would put a safeguarding record somewhere untyped.
 */
class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    /*
     * FOUND BY, and CALLED. Global search stays off until a resource answers both: `$recordTitle`
     * is what a result reads as in the list, and the attributes are what it matches on.
     *
     * Deliberately narrow. Searching a body of text finds every article that mentions a word, which
     * is a research tool rather than a way to reach the one record somebody has in mind.
     */
    protected static ?string $recordTitleAttribute = 'email';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Enquiry: '.\Illuminate\Support\Str::limit((string) $record->email, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 4;

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
            Forms\Components\TextInput::make('kind')->disabled(),
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\Select::make('age_stage_id')->relationship('ageStage', 'name')->label('Child\'s age')->disabled(),
            Forms\Components\Textarea::make('message')->disabled()->rows(5)->columnSpanFull(),

            Forms\Components\KeyValue::make('payload')
                ->label('Anything else the form collected')
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->options(['pending' => 'Pending', 'handled' => 'Handled', 'declined' => 'Declined'])
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
                Tables\Columns\TextColumn::make('kind')->badge(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('message')->limit(50)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'pending' ? 'warning' : ($state === 'handled' ? 'success' : 'gray')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->options([
                    'waitlist' => 'Waitlist', 'contact' => 'Contact', 'newsletter' => 'Newsletter',
                    'booking' => 'Booking', 'parent-guide' => 'Parent guide', 'career' => 'Career',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'handled' => 'Handled', 'declined' => 'Declined',
                ])->default('pending'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),
                Tables\Actions\Action::make('handled')
                    ->label('Mark handled')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Enquiry $record): bool => $record->status === 'pending')
                    ->action(fn (Enquiry $record) => $record->update([
                        'status' => 'handled', 'handled_at' => now(), 'handled_by' => auth()->id(),
                    ])),
            ])
            /*
             * AN EMPTY INBOX HAS TO SAY WHICH INBOX IT IS.
             *
             * Filament's default is "No records found", which on the Waitlist tab is
             * indistinguishable from a waitlist that was never built — and that is exactly how it
             * got read. Nothing arrives here until somebody uses the form on the site, so the empty
             * state names the form, says which page it sits on, and stops a quiet week looking like
             * a missing feature.
             */
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateHeading(fn ($livewire): string => static::emptyHeading($livewire->activeTab ?? null))
            ->emptyStateDescription(fn ($livewire): string => static::emptyBody($livewire->activeTab ?? null));
    }

    /** @param string|null $tab The active tab, which is the enquiry kind, or `all`. */
    private static function emptyHeading(?string $tab): string
    {
        return match ($tab) {
            'waitlist' => 'Nobody on the waitlist yet',
            'newsletter' => 'No subscribers yet',
            'contact' => 'No messages yet',
            'booking' => 'No calls requested yet',
            'parent-guide' => 'Nobody has asked for the guide yet',
            default => 'Nothing here yet',
        };
    }

    /**
     * Where each kind comes from.
     *
     * Naming the form and the page it sits on turns "is this broken?" into "nobody has filled it
     * in" — very different questions to be left holding, and only one of them is worth anybody's
     * afternoon.
     */
    private static function emptyBody(?string $tab): string
    {
        return match ($tab) {
            'waitlist' => 'Waitlist signups arrive here, from the Join Waitlist button in the site footer and the waitlist form.',
            'newsletter' => 'Subscribers arrive here, from the Subscribe box in the footer of the Journal, Circles and Stories pages.',
            'contact' => 'Messages arrive here, from the form on the Contact page.',
            'booking' => 'Call requests arrive here, from Book a call in the site navigation. The time is not held until somebody replies.',
            'parent-guide' => 'Requests arrive here, from the Get the guide form at the foot of the homepage.',
            default => 'Waitlist signups, subscribers, contact messages, call bookings and parent-guide requests all arrive here as people use the site.',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnquiries::route('/'),
            'edit' => Pages\EditEnquiry::route('/{record}/edit'),
        ];
    }
}
