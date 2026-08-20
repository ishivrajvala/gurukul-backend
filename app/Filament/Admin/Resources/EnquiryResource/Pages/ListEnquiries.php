<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EnquiryResource\Pages;

use App\Filament\Admin\Resources\EnquiryResource;
use App\Models\Enquiry;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        /* No create: these arrive from the website. */
        return [];
    }

    /**
     * A tab per kind.
     *
     * FIVE FORMS SHARE THIS TABLE, and that is still right — five near-identical tables would be
     * five near-identical screens, which is five places to forget to look. But it made the waitlist
     * and the newsletter INVISIBLE: each was one value in a dropdown filter, so unless somebody
     * already knew they were in here there was no way to find out. "Where is the waitlist?" is the
     * correct question to ask of that design.
     *
     * Tabs fix the discovery without splitting the table. Each carries a count, so the row also
     * answers what is waiting where before anything is clicked.
     *
     * PENDING ONLY IN THE COUNTS. A badge that includes handled records climbs for ever, stops
     * meaning anything and gets ignored — the opposite of what a number on an inbox is for.
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge(Enquiry::where('status', 'pending')->count() ?: null)
                ->badgeColor('warning'),
        ];

        foreach ([
            'waitlist' => 'Waitlist',
            'newsletter' => 'Subscribers',
            'contact' => 'Contact',
            'booking' => 'Call bookings',
            'parent-guide' => 'Parent guide',
        ] as $kind => $label) {
            $pending = Enquiry::where('kind', $kind)->where('status', 'pending')->count();

            $tabs[$kind] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('kind', $kind))
                /* No badge at zero: a row of grey noughts reads worse than nothing at all. */
                ->badge($pending ?: null)
                ->badgeColor('warning');
        }

        return $tabs;
    }
}
