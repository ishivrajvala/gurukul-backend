<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\EnquiryResource;
use App\Models\Enquiry;
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

    protected static ?string $heading = 'Latest enquiries';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enquiry::query()
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
                    ->tooltip(fn (Enquiry $record): string => $record->created_at->format('j M Y, H:i')),

                Tables\Columns\TextColumn::make('kind')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'newsletter' => 'Subscriber',
                        'parent-guide' => 'Parent guide',
                        'booking' => 'Call booking',
                        default => ucfirst($state),
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')->placeholder('No name given'),

                Tables\Columns\TextColumn::make('email')->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pending' ? 'warning' : 'success'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->url(fn (Enquiry $record): string => EnquiryResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-arrow-up-right')
                    ->label('Open'),
            ])
            ->emptyStateHeading('Nothing yet')
            ->emptyStateDescription('Enquiries from the waitlist, contact, subscribe, booking and parent guide forms arrive here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
