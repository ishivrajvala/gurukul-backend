<?php

declare(strict_types=1);

namespace App\Filament\Admin\Clusters;

use App\Models\CircleQuestion;
use App\Models\CircleSignup;
use App\Models\StorySubmission;
use Filament\Clusters\Cluster;

/**
 * The three things a parent SENDS US that are not a lead.
 *
 * ONE NAV ENTRY, THREE TABLES, and the tables stay three on purpose. Circle signups, circle
 * questions and story submissions each carry a rule the schema itself has to hold — a WhatsApp
 * number, an anonymity flag, a reproducible consent — and folding them into one table with a
 * `payload` blob would put a safeguarding record inside untyped JSON. That argument is written out
 * in `Lead`, which is the table that DID merge four forms, and it still holds.
 *
 * What was wrong was the navigation, not the schema. The inbox listed five separate items and every
 * one of them was "somebody sent us something"; the person opening the panel in the morning wants
 * one place that answers "what came in", not a column of five to check in turn. A cluster gives
 * exactly that — one entry, three tabs behind it — without merging anything underneath.
 *
 * THE BADGE IS THE SUM. A cluster does not add up its children's badges by itself, and three
 * separate counts hidden behind one closed entry is worse than no count at all: it reads as nothing
 * waiting. This counts every unhandled row across all three.
 */
class Requests extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $slug = 'requests';

    protected static ?string $clusterBreadcrumb = 'Requests';

    public static function getNavigationBadge(): ?string
    {
        $waiting = CircleSignup::query()->where('status', 'pending')->count()
            + CircleQuestion::query()->where('status', 'pending')->count()
            + StorySubmission::query()->where('status', 'pending')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() === null ? null : 'warning';
    }
}
