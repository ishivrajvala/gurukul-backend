<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\CircleSignupResource;
use App\Filament\Admin\Resources\LeadResource;
use App\Filament\Admin\Resources\JobApplicationResource;
use App\Filament\Admin\Resources\StorySubmissionResource;
use App\Models\CircleQuestion;
use App\Models\CircleSignup;
use App\Models\Lead;
use App\Models\JobApplication;
use App\Models\StorySubmission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What is waiting for a person.
 *
 * THIS REPLACED `AccountWidget` AND `FilamentInfoWidget` — a card telling you who you are signed in
 * as, and an advertisement for the framework. Neither answers the question somebody opens an admin
 * to ask, which on this site is always the same one: has anybody been in touch, and is anybody
 * waiting on me.
 *
 * PENDING ONLY, NEVER TOTALS. A running total climbs for ever and stops meaning anything; the
 * number that matters is the one that should be zero by the end of the week. A story submission or
 * a job application sitting unread is somebody waiting on us, and the cost of not looking falls on
 * them rather than on us — which is why they are here rather than in a report.
 *
 * EVERY STAT IS A LINK. A number that tells you there is work and makes you go and find it is a
 * number people stop reading.
 */
class InboxOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $signups = CircleSignup::where('status', 'pending')->count();
        $questions = CircleQuestion::where('status', 'pending')->count();
        $stories = StorySubmission::where('status', 'pending')->count();
        $applications = JobApplication::where('status', 'pending')->count();
        /* OPEN, not `pending`: each kind has its own words for unfinished — see `LeadKind`. */
        $leads = Lead::query()->open()->count();

        return [
            Stat::make('Circle requests', $signups)
                ->description($signups ? 'Waiting for a WhatsApp invite' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($signups ? 'warning' : 'gray')
                ->url(CircleSignupResource::getUrl()),

            Stat::make('Leads', $leads)
                ->description($leads ? 'Waitlist, contact, call bookings, parent guide' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color($leads ? 'warning' : 'gray')
                ->url(LeadResource::getUrl()),

            Stat::make('Story submissions', $stories)
                ->description($stories ? 'Families who have written in' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-heart')
                ->color($stories ? 'warning' : 'gray')
                ->url(StorySubmissionResource::getUrl()),

            Stat::make('Applications', $applications)
                ->description($applications ? 'Read these before they take another job' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-identification')
                ->color($applications ? 'danger' : 'gray')
                ->url(JobApplicationResource::getUrl()),

            Stat::make('Questions asked', $questions)
                ->description($questions ? 'From the Circles and the Journal' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color($questions ? 'warning' : 'gray')
                ->url(\App\Filament\Admin\Resources\CircleQuestionResource::getUrl()),
        ];
    }
}
