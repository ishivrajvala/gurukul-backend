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

    protected static ?string $navigationIcon = 'heroicon-o-tray';

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
                    ->color(fn (string $s): string => $s === 'pending' ? 'warning' : ($s === 'handled' ? 'success' : 'gray')),
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
                    ->visible(fn (Enquiry $r): bool => $r->status === 'pending')
                    ->action(fn (Enquiry $r) => $r->update([
                        'status' => 'handled', 'handled_at' => now(), 'handled_by' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnquiries::route('/'),
            'edit' => Pages\EditEnquiry::route('/{record}/edit'),
        ];
    }
}
