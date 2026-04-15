<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use Illuminate\Http\JsonResponse;

class LandingPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $landingPage = LandingPage::query()
            ->where('status', 'published')
            ->where('slug', $slug)
            ->with([
                'seoMeta',
                'sections' => fn ($query) => $query->orderBy('order'),
            ])
            ->firstOrFail();

        return response()->json([
            'title' => $landingPage->title,
            'slug' => $landingPage->slug,
            'sections' => $landingPage->sections
                ->map(fn ($section): array => [
                    'type' => $section->type,
                    'data' => $section->data,
                ])
                ->values(),
            'seo' => $landingPage->seoMeta ? [
                'meta_title' => $landingPage->seoMeta->meta_title,
                'meta_description' => $landingPage->seoMeta->meta_description,
                'og_image' => $landingPage->seoMeta->og_image,
                'canonical_url' => $landingPage->seoMeta->canonical_url,
            ] : null,
        ]);
    }
}
