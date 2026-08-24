<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CampaignResource\RelationManagers;

use App\Models\CampaignRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * WHO GOT IT, AND WHO DID NOT.
 *
 * The counts on the campaign say four failed. Only these rows say WHICH four, on what grounds, and
 * when — and only these let a bounce that arrives days later be traced back to the send that caused
 * it. Everything on the campaign itself is a cache of what is here.
 *
 * READ ONLY. A delivery record is a statement about something that already happened; editing one
 * would be editing history, and the one time it matters is the one time somebody is trying to work
 * out what actually went out.
 */
class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Who it went to';

    protected static ?string $modelLabel = 'recipient';

    /* Hidden until there is something to show — an empty tab on every draft is furniture. */
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->recipients()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CampaignRecipient::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        CampaignRecipient::STATUS_SENT => 'success',
                        CampaignRecipient::STATUS_QUEUED => 'gray',
                        CampaignRecipient::STATUS_BOUNCED,
                        CampaignRecipient::STATUS_COMPLAINED,
                        CampaignRecipient::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sent_at')->label('Sent')->dateTime('j M, H:i')->placeholder('—'),

                /*
                 * THE REASON, WRAPPED AND IN FULL ON HOVER. A truncated SMTP error is worth almost
                 * nothing — the useful part is usually at the end of the line.
                 */
                Tables\Columns\TextColumn::make('error')
                    ->label('What went wrong')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CampaignRecipient::STATUSES),
            ])
            ->headerActions([])
            ->actions([])
            ->emptyStateHeading('Not sent yet');
    }
}
