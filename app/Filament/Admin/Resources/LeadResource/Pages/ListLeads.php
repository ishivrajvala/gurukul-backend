<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeadResource\Pages;

use App\Filament\Admin\Resources\LeadResource;
use App\Models\Lead;
use App\Models\LeadKind;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: these arrive from the website. */
        return [];
    }

    /**
     * A tab per form.
     *
     * FOUR FORMS SHARE THIS TABLE, and that is still right — four near-identical tables would be
     * four near-identical screens, which is four places to forget to look. But sharing it made the
     * waitlist INVISIBLE: it was one value in a dropdown filter, so unless somebody already knew it
     * was in here there was no way to find out. "Where is the waitlist?" is the correct question to
     * ask of that design.
     *
     * OPEN ONLY IN THE COUNTS, and "open" is per kind — an invited family and a scheduled call are
     * still ours to chase, a declined one is not. A badge that counts finished rows climbs for ever
     * and gets ignored, which is the opposite of what a number on an inbox is for.
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge(Lead::query()->open()->count() ?: null)
                ->badgeColor('warning'),
        ];

        foreach (LeadKind::cases() as $kind) {
            $open = Lead::query()
                ->where('kind', $kind->value)
                ->whereIn('status', $kind->openStatuses())
                ->count();

            $tabs[$kind->value] = Tab::make($kind->label())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('kind', $kind->value))
                /* No badge at zero: a row of grey noughts reads worse than nothing at all. */
                ->badge($open ?: null)
                ->badgeColor('warning');
        }

        return $tabs;
    }
}
