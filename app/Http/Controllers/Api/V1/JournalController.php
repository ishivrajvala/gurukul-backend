<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
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

    public function show(string $slug): JsonResponse
    {
        $article = Article::published()
            ->with(['topic', 'ageStages'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->summary($article) + ['bodyHtml' => $article->content],
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
            'readingMinutes' => $a->reading_minutes,
            'isFeatured' => $a->is_featured,
            'publishedAt' => $a->published_at?->toDateString(),
            'image' => $a->featured_image
                ? ['src' => $a->featured_image, 'alt' => $a->featured_image_alt]
                : null,
        ];
    }
}
