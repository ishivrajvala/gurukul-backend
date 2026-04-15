<?php

namespace App\Application\Services\Blog;

use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BlogIndexService
{
    public function execute(?string $categorySlug, ?string $tagSlug, ?string $featured): LengthAwarePaginator
    {
        return Blog::query()
            ->where('status', 'published')
            ->when($categorySlug, function (Builder $query, string $slug): void {
                $query->whereHas('category', function (Builder $categoryQuery) use ($slug): void {
                    $categoryQuery->where('slug', $slug);
                });
            })
            ->when($tagSlug, function (Builder $query, string $slug): void {
                $query->whereHas('tags', function (Builder $tagQuery) use ($slug): void {
                    $tagQuery->where('slug', $slug);
                });
            })
            ->when($this->shouldFilterFeatured($featured), function (Builder $query): void {
                $query->where('is_featured', true);
            })
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(10);
    }

    /**
     * @return array{
     *     blog: Blog,
     *     featured_blogs: Collection<int, Blog>,
     *     trending_tags: Collection<int, BlogTag>
     * }
     */
    public function showBySlug(string $slug): array
    {
        $blog = Blog::query()
            ->where('status', 'published')
            ->where('slug', $slug)
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'seoMeta'])
            ->firstOrFail();

        $featuredBlogs = Blog::query()
            ->where('status', 'published')
            ->where('is_featured', true)
            ->whereKeyNot($blog->id)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'featured_image']);

        $trendingTags = BlogTag::query()
            ->select(['id', 'name', 'slug'])
            ->withCount('blogs')
            ->orderByDesc('blogs_count')
            ->limit(5)
            ->get();

        return [
            'blog' => $blog,
            'featured_blogs' => $featuredBlogs,
            'trending_tags' => $trendingTags,
        ];
    }

    private function shouldFilterFeatured(?string $featured): bool
    {
        return filter_var($featured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }
}
