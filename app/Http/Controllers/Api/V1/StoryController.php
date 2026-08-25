<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommunityReview;
use App\Models\Story;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Parent Stories, the short testimonials, and the community screenshots.
 *
 * `body` IS ONLY ON THE DETAIL. A list of two dozen stories should not drag every paragraph of
 * every one through it, which is the same reason the frontend keeps its index and its copy in two
 * files joined on slug.
 *
 * REVIEWS COME FROM `publishable()` AND NOTHING ELSE. Google, Instagram and YouTube comments are
 * public; WhatsApp messages and emails are private, and republishing one without the family
 * agreeing is a breach whatever it says. The scope lives on the model so no controller can forget
 * it, and there is no query parameter that can turn it off.
 *
 * No rating, no total, and nothing here that could be aggregated into "4.9 from 2,847 parents".
 */
class StoryController extends Controller
{
    public function index(): JsonResponse
    {
        $stories = Story::published()
            ->with(['storyTag', 'petal', 'ageStages'])
            ->get()
            ->map(fn (Story $s): array => $this->summary($s));

        return response()->json(['data' => $stories]);
    }

    /**
     * The five on the homepage, in the order an editor chose, the lead first.
     *
     * ITS OWN ENDPOINT rather than a flag on the feed, because the ORDER is the answer. The
     * homepage band asks "which five, and which one leads"; sending all eight with a nullable
     * position and re-sorting on the client puts that question in two places, and the client would
     * be the one that got it wrong.
     *
     * `published()` is applied as well as the slot: a story pulled from the site should leave the
     * homepage without anybody remembering to clear its slot too.
     */
    public function home(): JsonResponse
    {
        $stories = Story::published()
            ->onHome()
            ->with(['storyTag', 'petal', 'ageStages'])
            ->get()
            ->map(fn (Story $s): array => $this->summary($s));

        return response()->json(['data' => $stories]);
    }

    public function show(string $slug): JsonResponse
    {
        $story = Story::published()
            ->with(['storyTag', 'petal', 'ageStages'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->summary($story) + [
                /* Plain paragraphs, never HTML: a parent story is somebody talking, so there is no
                   markup to render and therefore none for a consumer to sanitise. */
                'body' => $story->body ?? [],
            ],
        ]);
    }

    public function testimonials(): JsonResponse
    {
        $rows = Testimonial::published()->get()->map(fn (Testimonial $t): array => [
            'slug' => $t->slug,
            'quote' => $t->content,
            /* The pathway family, never a name and never a photograph. */
            'family' => $t->family_label,
        ]);

        return response()->json(['data' => $rows]);
    }

    public function reviews(): JsonResponse
    {
        $rows = CommunityReview::publishable()->get()->map(fn (CommunityReview $r): array => [
            /*
             * A stable key for the client's list. There is no slug column and there should not be
             * one: a screenshot has no name, only a position on a wall. Indexing the array instead
             * would reorder every tile the moment one review is unpublished.
             */
            'id' => $r->id,
            'source' => $r->source,
            'image' => [
                /*
                 * An ABSOLUTE URL, not the stored path. The consumer is a different origin and
                 * cannot resolve `stories/reviews/x.png` against anything; it would render a
                 * broken tile per review and nothing in either log would say why.
                 */
                'src' => Storage::disk('public')->url($r->image_path),
                'alt' => $r->image_alt,
                'width' => $r->image_width,
                'height' => $r->image_height,
            ],
        ]);

        return response()->json(['data' => $rows]);
    }

    private function summary(Story $s): array
    {
        return [
            'slug' => $s->slug,
            'title' => $s->title,
            'standfirst' => $s->standfirst,
            'tag' => $s->storyTag?->slug,
            /* The petal is the EYEBROW, the tag is the FILTER. Different questions on purpose. */
            'petal' => $s->petal?->name,
            'ages' => $s->ageStages->pluck('key'),
            'author' => ['name' => $s->author_name, 'relation' => $s->author_relation],
            'place' => $s->place,
            'media' => $s->isVideo()
                ? [
                    'kind' => 'video',
                    'youtubeId' => $s->youtube_id,
                    'aspect' => $s->media_aspect,
                    'durationMinutes' => $s->duration_minutes,
                ]
                : ['kind' => 'written'],
            'readingMinutes' => $s->reading_minutes,
            'isFeatured' => $s->is_featured,
            'publishedAt' => $s->published_at?->toDateString(),
        ];
    }
}
