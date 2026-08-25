<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MostAsked;
use App\Models\TrendingSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    /**
     * The feed, optionally searched.
     *
     * SEARCH IS SERVER-SIDE, and it did not used to be. The site fetched the whole archive once and
     * filtered it in the browser, which was fine at thirty-six rows and stopped being fine at a
     * hundred and seventy-seven: every reader downloads every article's title and standfirst before
     * they can type a letter, and the search can only ever match what happens to be in that payload.
     *
     * Moving it here also means one definition of what "matching" means. The browser matched title
     * and standfirst; the sitemap, any future related-articles query and this endpoint would each
     * have grown their own. `ILIKE` on both fields is deliberately the same rule as before — this
     * change is about WHERE it runs, not about making the results different.
     *
     * NO PAGINATION, on purpose. The consumer pages the feed itself (`INITIAL_VISIBLE`, then eight
     * at a time) and needs the full result set to do it, plus the counts per topic in the sidebar.
     * A hundred and seventy-seven summaries is a small payload; when it stops being one, the
     * consumer's paging is what has to move, not this.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::published()->with(['articleTopic', 'ageStages']);

        $term = trim((string) $request->query('q', ''));

        /* Two characters, matching the typeahead's own floor. A single letter matches most of the
           archive and is never what somebody meant. */
        if (mb_strlen($term) >= 2) {
            /* Escape the LIKE wildcards. Without this a reader typing `%` matches everything and
               `_` matches any single character, which reads as the search being broken. */
            $escaped = addcslashes($term, '%_\\');

            $query->where(function (Builder $q) use ($escaped): void {
                $q->where('title', 'ILIKE', '%'.$escaped.'%')
                    ->orWhere('standfirst', 'ILIKE', '%'.$escaped.'%');
            });
        }

        $articles = $query->get()->map(fn (Article $a): array => $this->summary($a));

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
            ->with(['articleTopic', 'ageStages', 'seo'])
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
            'topic' => $a->articleTopic?->slug,
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
