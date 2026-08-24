<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\LeadResource;
use App\Models\Lead;
use App\Models\LeadKind;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The last few people who wrote in, whatever form they used.
 *
 * THE COUNTS ABOVE SAY HOW MUCH; THIS SAYS WHO. A number alone tells somebody there is work and
 * makes them go and look for it, and the thing they actually want to know first is whether the
 * newest one needs answering today.
 *
 * ENQUIRIES ONLY, not every inbox merged. Five forms already share this table, so it is the one
 * list where "the last few" is meaningful; stitching four unrelated models into one feed would need
 * a union and would produce rows a person cannot act on without opening each in a different screen.
 */
class RecentSubmissions extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest leads';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    /* Pending first, then newest. Somebody scanning this wants the unanswered ones
                       at the top, not the most recent regardless of whether it is dealt with. */
                    ->orderByRaw("case when status = 'pending' then 0 else 1 end")
                    ->latest('created_at')
                    ->limit(8),
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn (Lead $record): string => $record->created_at->format('j M Y, H:i')),

                /*
                 * The label comes from `LeadKind`, not from a `match` restating the four names.
                 * `kind` is cast to the enum on the model, so `$state` arrives as a LeadKind and
                 * never as a string — which is what the old signature assumed, and what broke it.
                 */
                Tables\Columns\TextColumn::make('kind')
                    ->label('Form')
                    ->badge()
                    ->formatStateUsing(fn (?LeadKind $state): string => $state?->label() ?? '—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')->placeholder('No name given'),

                Tables\Columns\TextColumn::make('email')->copyable(),

                /*
                 * Each kind has its own vocabulary now, so the label and the colour both come from
                 * the record's kind — "Scheduled" means something on a booking and does not exist
                 * on a contact message.
                 */
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Lead $record): string => $record->statusLabel())
                    ->color(fn (Lead $record): string => $record->kind?->statusColour($record->status) ?? 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->url(fn (Lead $record): string => LeadResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-arrow-up-right')
                    ->label('Open'),
            ])
            ->emptyStateHeading('Nothing yet')
            ->emptyStateDescription('Leads from the waitlist, contact, book a call and parent guide forms arrive here. Subscribers are not leads — they are on the email list.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
