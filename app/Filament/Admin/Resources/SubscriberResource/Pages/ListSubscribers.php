<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SubscriberResource\Pages;

use App\Filament\Admin\Resources\CampaignResource;
use App\Filament\Admin\Resources\SubscriberResource;
use App\Models\Campaign;
use App\Models\Subscriber;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        /*
         * WRITE TO THE LIST, FROM THE LIST.
         *
         * There is no Create here — see `SubscriberResource::canCreate`, consent is the asset — but
         * the one thing somebody looking at these addresses actually wants to do is write to them,
         * and that used to mean knowing that a separate top-level menu called Campaigns existed.
         *
         * It opens a DRAFT rather than sending anything. Nothing leaves until it is written and
         * sent deliberately, and `Campaign::markSending` holds the lock that stops the same email
         * going out twice. The count in the button is the deliverable list as it stands right now,
         * which is the number the person writing needs to see before they start.
         */
        return [
            Actions\Action::make('emailEveryone')
                ->label(fn (): string => 'Email all '.Subscriber::query()->deliverable()->count().' subscribers')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => Subscriber::query()->deliverable()->exists())
                ->action(function (): void {
                    $campaign = Campaign::create([
                        'subject' => '',
                        'status' => Campaign::STATUS_DRAFT,
                        'created_by' => auth()->id(),
                    ]);

                    $this->redirect(CampaignResource::getUrl('edit', ['record' => $campaign]));
                }),
        ];
    }

    /**
     * Three tabs, and the two that are not "Subscribed" are the ones worth having.
     *
     * A list screen that only ever shows live addresses cannot answer the question that actually
     * costs money: how many are bouncing. Bounced addresses are invisible by default — the filter
     * starts on Subscribed — so without a tab carrying the count, a domain quietly failing to
     * deliver looks exactly like a list that is not growing.
     *
     * THE CLOSURE PARAMETER HAS TO BE CALLED `$query`, and that is not a style note.
     *
     * Filament resolves closure arguments BY NAME, not by type. Written as `fn (Builder $q)` the
     * name matches nothing it knows how to supply, so it passes null, `Tab::modifyQuery` returns
     * null in place of a builder, and the table ends up with no query at all. That surfaces four
     * layers away as `Cannot use "::class" on null` inside Filament's own filter form, naming no
     * file of ours and pointing at no line worth reading — the page simply 500s.
     */
    public function getTabs(): array
    {
        return [
            'subscribed' => Tab::make('Subscribed')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Subscriber::STATUS_SUBSCRIBED))
                ->badge(Subscriber::query()->deliverable()->count() ?: null)
                ->badgeColor('success'),

            'bounced' => Tab::make('Bounced')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Subscriber::STATUS_BOUNCED))
                ->badge(Subscriber::where('status', Subscriber::STATUS_BOUNCED)->count() ?: null)
                ->badgeColor('danger'),

            'unsubscribed' => Tab::make('Unsubscribed')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Subscriber::STATUS_UNSUBSCRIBED)),

            'all' => Tab::make('All'),
        ];
    }
}
