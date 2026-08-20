<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\Gathering;
use Illuminate\Http\JsonResponse;

/**
 * Parent Circles, and the gatherings inside them.
 *
 * WHAT THIS DELIBERATELY DOES NOT RETURN: any member count, activity measure, popularity or rank,
 * and no raw `taken`. `availability` is one of three phrases because the locked attention-as-sacred
 * rule bans enrollment counts — a small facilitated group must not read as a filling scoreboard.
 * Exposing `taken` here would let any consumer rebuild the scoreboard the site refuses to draw.
 *
 * `ages: []` MEANS EVERY STAGE, not none. That is what makes the universal circle universal, and
 * `audience` carries the rendered form so a consumer never has to reimplement the rule.
 */
class CircleController extends Controller
{
    public function index(): JsonResponse
    {
        $circles = Circle::published()
            ->ordered()
            ->with(['ageStages', 'topics'])
            ->get()
            ->map(fn (Circle $c): array => $this->circle($c));

        return response()->json(['data' => $circles]);
    }

    public function show(string $slug): JsonResponse
    {
        $circle = Circle::published()
            ->with(['ageStages', 'topics', 'petals'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->circle($circle) + [
                /* Petals appear on the DETAIL only. They are recorded so the mapping is written
                   down, not so a parent has to read the architecture before joining. */
                'petals' => $circle->petals->pluck('name'),
                'gatherings' => [
                    'upcoming' => Gathering::published()->where('circle_id', $circle->id)
                        ->upcoming()->with('topic')->get()
                        ->map(fn (Gathering $g): array => $this->gathering($g)),
                    'past' => Gathering::published()->where('circle_id', $circle->id)
                        ->past()->with('topic')->get()
                        ->map(fn (Gathering $g): array => $this->gathering($g)),
                ],
            ],
        ]);
    }

    /** Upcoming only. The site's list offers what a parent can still say yes to. */
    public function gatherings(): JsonResponse
    {
        $rows = Gathering::published()
            ->upcoming()
            ->with(['circle', 'topic', 'ageStages'])
            ->get()
            ->map(fn (Gathering $g): array => $this->gathering($g) + [
                'circle' => ['slug' => $g->circle?->slug, 'name' => $g->circle?->name],
                'ages' => $g->ageStages->pluck('key'),
            ]);

        return response()->json(['data' => $rows]);
    }

    private function circle(Circle $c): array
    {
        return [
            'slug' => $c->slug,
            'name' => $c->name,
            'purpose' => $c->purpose,
            'description' => $c->description,
            'managedByAvdhara' => $c->managed_by_avdhara,
            'isFeatured' => $c->is_featured,
            'ages' => $c->ageStages->pluck('key'),
            'audience' => $c->audienceLabel(),
            'topics' => $c->topics->pluck('slug'),
        ];
    }

    private function gathering(Gathering $g): array
    {
        return [
            'slug' => $g->slug,
            'title' => $g->title,
            'description' => $g->description,
            'topic' => $g->topic?->slug,
            'startsAt' => $g->starts_at?->toIso8601String(),
            'format' => $g->format,
            'city' => $g->city,
            /* A phrase, never a number. See the class note. */
            'availability' => $g->availabilityLabel(),
            'formatLabel' => $g->formatLabel(),
        ];
    }
}
