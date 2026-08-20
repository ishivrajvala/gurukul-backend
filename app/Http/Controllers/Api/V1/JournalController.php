<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MostAsked;
use App\Models\TrendingSearch;
use Illuminate\Http\JsonResponse;

/**
 * The Parent Journal.
 *
 * `content` IS EDITOR HTML from the Tiptap editor, and it is only on the detail endpoint. Any
 * consumer must sanitise it before rendering — the frontend has exactly one component that does,
 * and one that touches `bodyHtml` at all.
 *
 * A row without content is COMMISSIONED BUT UNWRITTEN, which is a real state rather than an error:
 * `published()` requires a status and a date that has passed, so an unwritten piece simply does not
 * appear rather than showing as an empty article.
 */
class JournalController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = Article::published()
            ->with(['topic', 'ageStages'])
            ->get()
            ->map(fn (Article $a): array => $this->summary($a));

        return response()->json(['data' => $articles]);
    }

    /**
     * The furniture around the search field: the most-asked list and the trending terms.
     *
     * ONE CALL, NOT TWO. They are two tables because they are two different things to edit, but they
     * are rendered within a hundred pixels of each other and neither is any use without the page
     * that holds the other. Two endpoints would be two round trips for one strip of UI.
     *
     * NOT UNDER `/journal/{slug}`. A route of `/journal/meta` would be a slug away from a real
     * article for the rest of this project's life — the first piece somebody files under the slug
     * `meta` would shadow it, and the failure would look like a broken article rather than a route
     * collision.
     */
    public function meta(): JsonResponse
    {
        return response()->json([
            'data' => [
                'mostAsked' => MostAsked::answered()
                    ->with('article:id,slug')
                    ->get()
                    ->map(fn (MostAsked $m): array => [
                        'question' => $m->question,
                        'slug' => $m->article->slug,
                    ]),
                'trending' => TrendingSearch::ordered()->pluck('term'),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::published()
            ->with(['topic', 'ageStages', 'seo'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->summary($article) + [
                'bodyHtml' => $article->content,
                /*
                 * SEARCH METADATA, and null throughout when nobody has written any.
                 *
                 * Only on the detail endpoint: it is what a reading page's `<head>` needs, and the
                 * feed of thirty-six rows has no use for thirty-six meta descriptions. The consumer
                 * falls back to the title and standfirst field by field, so a row with one value
                 * filled in overrides only that one.
                 */
                'seo' => [
                    'title' => $article->seo?->meta_title,
                    'description' => $article->seo?->meta_description,
                    'image' => $article->seo?->og_image,
                    'canonical' => $article->seo?->canonical_url,
                ],
            ],
        ]);
    }

    private function summary(Article $a): array
    {
        return [
            'slug' => $a->slug,
            'title' => $a->title,
            'standfirst' => $a->standfirst ?? $a->short_description,
            'topic' => $a->topic?->slug,
            'ages' => $a->ageStages->pluck('key'),
            /*
             * WHETHER THE PIECE IS WRITTEN, without carrying the writing.
             *
             * A row can be published and still have no body: commissioned-but-unwritten is a real
             * modelled state, and those pages render a "being written" panel and carry `noindex`.
             * The consumer's sitemap has to exclude exactly those, and its only other option was to
             * fetch thirty-six article bodies to find out which — or, as it actually did, guess
             * from a local copy of the archive and quietly drop every CMS-written piece.
             */
            'hasBody' => filled($a->content),
            'readingMinutes' => $a->reading_minutes,
            'isFeatured' => $a->is_featured,
            'publishedAt' => $a->published_at?->toDateString(),
            'image' => $a->featured_image
                ? ['src' => $a->featured_image, 'alt' => $a->featured_image_alt]
                : null,
        ];
    }
}
