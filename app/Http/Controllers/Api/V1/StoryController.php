<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommunityReview;
use App\Models\Story;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

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
            ->with(['topic', 'petal', 'ageStages'])
            ->get()
            ->map(fn (Story $s): array => $this->summary($s));

        return response()->json(['data' => $stories]);
    }

    public function show(string $slug): JsonResponse
    {
        $story = Story::published()
            ->with(['topic', 'petal', 'ageStages'])
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
            'source' => $r->source,
            'image' => [
                'src' => $r->image_path,
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
            'topic' => $s->topic?->slug,
            /* The petal is the EYEBROW, the topic is the FILTER. Different questions on purpose. */
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
