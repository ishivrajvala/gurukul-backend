<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CircleSignup;
use App\Models\Lead;
use App\Models\StorySubmission;
use App\Models\Subscriber;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * TWELVE WEEKS OF ARRIVALS — the one thing on this dashboard that is not a number.
 *
 * WHY A CHART AT ALL, on a panel whose locked rules forbid vanity metrics. This is not a vanity
 * metric and the distinction is the whole justification: the stat tiles above answer "is anybody
 * waiting on me right now", which is today's work. Neither they nor a table can answer "is this
 * quieter than usual" — and that question is how a broken form is noticed. A contact form that
 * silently stopped posting looks exactly like a quiet fortnight until you can see the shape of the
 * last three months.
 *
 * NO VIEWS, LIKES, RATINGS OR ENROLMENTS ANYWHERE NEAR IT. Every series here is a person who chose
 * to get in touch, which is the only kind of number the site shows about itself.
 *
 * WEEKS, NOT DAYS. Daily buckets on a site this size are mostly noise around zero, and a sawtooth
 * that touches the axis every Sunday hides the trend it exists to show. Twelve weeks is one
 * season — long enough for a shape, short enough that a dip is still recent enough to act on.
 */
class ArrivalsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Who got in touch';

    protected static ?string $description = 'Every person who filled something in, by week, for the last twelve weeks.';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $weeks = collect(range(11, 0))->map(
            fn (int $back): CarbonImmutable => CarbonImmutable::now()->startOfWeek()->subWeeks($back),
        );

        return [
            'datasets' => [
                /*
                 * THE COLOURS ARE THE BRAND'S FOUR, and each is used for what it means elsewhere in
                 * the panel: indigo for leads (the primary), marigold for subscribers (the accent),
                 * green for the community. A fourth line would need a fifth colour that does not
                 * exist, which is the real reason there are three series and not six.
                 */
                $this->series('Leads', '#27156B', $weeks, $this->timestamps(Lead::query(), $weeks)),
                $this->series('Subscribers', '#F7B75F', $weeks, $this->timestamps(Subscriber::query(), $weeks)),
                /*
                 * CIRCLE SIGNUPS AND STORY SUBMISSIONS AS ONE LINE. Separately each is a handful a
                 * week, and two lines hugging the axis say less than one. They are also the same
                 * thing from a reader's point of view — somebody stepping from reading the site
                 * into taking part in it.
                 */
                $this->series('Circles & stories', '#56A195', $weeks, $this->timestamps(
                    CircleSignup::query(),
                    $weeks,
                )->merge($this->timestamps(StorySubmission::query(), $weeks))),
            ],
            'labels' => $weeks->map(fn (CarbonImmutable $w): string => $w->format('j M'))->all(),
        ];
    }

    /**
     * The `created_at` of every row of this kind since the window opened, as week-start keys.
     *
     * ONE QUERY PER SERIES, bucketed in PHP. Twelve `count()` calls per line would be thirty-six
     * queries on a widget that renders on every load of the home screen — exactly the place where
     * a loop of counts quietly becomes the slowest thing in the panel. Selecting one column keeps
     * it cheap even when the tables are large.
     */
    private function timestamps(Builder $query, $weeks): Collection
    {
        return $query
            ->where('created_at', '>=', $weeks->first())
            ->pluck('created_at')
            ->map(fn ($at): string => CarbonImmutable::parse($at)->startOfWeek()->toDateString());
    }

    /** One line, from a bag of week-start keys. */
    private function series(string $label, string $colour, $weeks, Collection $stamps): array
    {
        $byWeek = $stamps->countBy();

        return [
            'label' => $label,
            'data' => $weeks->map(fn (CarbonImmutable $week): int => $byWeek->get($week->toDateString(), 0))->all(),
            'borderColor' => $colour,
            'backgroundColor' => $colour.'1F',
            'fill' => true,
            /* Curved rather than angular: three straight lines crossing each other is hard to read. */
            'tension' => 0.35,
            'borderWidth' => 2,
            'pointRadius' => 0,
            'pointHoverRadius' => 4,
            'pointHoverBackgroundColor' => $colour,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 8,
                        'padding' => 18,
                        'font' => ['family' => 'Baloo 2', 'size' => 13],
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    /*
                     * INTEGER TICKS. These are people; a y-axis offering to show 2.5 of one is the
                     * kind of detail that makes a dashboard look automatically generated.
                     */
                    'ticks' => ['precision' => 0, 'font' => ['family' => 'Baloo 2']],
                    'grid' => ['color' => 'rgba(39, 21, 107, 0.06)'],
                ],
                'x' => [
                    'ticks' => ['font' => ['family' => 'Baloo 2']],
                    /* No vertical grid: it fights the lines it is supposed to sit behind. */
                    'grid' => ['display' => false],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
