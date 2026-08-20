<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Landing pages.
 *
 * TWO ENDPOINTS, AND THE LIST CARRIES NO SECTIONS. `index` exists so the consumer can prerender
 * every slug; dragging the full section stack of every page through it would be a payload nobody
 * uses, growing with each page published.
 *
 * `show` returns the sections a visitor should see, in order, with the unpublished ones already
 * removed — a consumer that has to filter is a consumer that can forget to.
 */
class LandingPageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = LandingPage::published()
            ->orderBy('slug')
            ->get()
            ->map(fn (LandingPage $p): array => ['slug' => $p->slug, 'title' => $p->title]);

        return response()->json(['data' => $pages]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = LandingPage::published()
            ->with(['seo', 'sections' => fn ($q) => $q->published()])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'seo' => [
                    'title' => $page->seo?->meta_title,
                    'description' => $page->seo?->meta_description,
                    'image' => $page->seo?->og_image,
                    'canonical' => $page->seo?->canonical_url,
                ],
                'sections' => $page->sections->map(fn (LandingSection $s): array => [
                    'type' => $s->type,
                    'data' => $this->withImageUrls($s->data ?? []),
                ]),
            ],
        ]);
    }

    /**
     * Uploaded images become absolute URLs.
     *
     * The consumer is a different origin and cannot resolve `landing/hero.webp` against anything;
     * it would render a broken picture per section with nothing in either log saying why. A value
     * that is already a path or a URL is left exactly as it is.
     */
    private function withImageUrls(array $data): array
    {
        if (blank($data['image'] ?? null)) {
            return $data;
        }

        $path = $data['image'];

        if (! str_starts_with($path, '/') && ! str_starts_with($path, 'http')) {
            $data['image'] = Storage::disk('public')->url($path);
        }

        return $data;
    }
}
