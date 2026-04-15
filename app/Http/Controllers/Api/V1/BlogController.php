<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Blog\BlogIndexService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlogIndexResource;
use App\Http\Resources\Api\V1\BlogShowResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogIndexService $blogIndexService
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $blogs = $this->blogIndexService->execute(
            $request->query('category'),
            $request->query('tag'),
            $request->query('featured')
        );

        return BlogIndexResource::collection($blogs);
    }

    public function show(string $slug): JsonResponse
    {
        $data = $this->blogIndexService->showBySlug($slug);

        return response()->json([
            'blog' => BlogShowResource::make($data['blog']),
            'featured_blogs' => collect($data['featured_blogs'])
                ->map(fn ($blog): array => [
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'featured_image' => $blog->featured_image,
                ])
                ->values(),
            'trending_tags' => collect($data['trending_tags'])
                ->map(fn ($tag): array => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'usage_count' => $tag->blogs_count,
                ])
                ->values(),
        ]);
    }
}
