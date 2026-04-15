<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Blog */
class BlogShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'category' => $this->whenLoaded('category', function (): ?array {
                if (! $this->category) {
                    return null;
                }

                return [
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function (): array {
                return $this->tags
                    ->map(fn ($tag): array => [
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ])
                    ->values()
                    ->all();
            }, []),
            'seo' => $this->whenLoaded('seoMeta', function (): ?array {
                if (! $this->seoMeta) {
                    return null;
                }

                return [
                    'meta_title' => $this->seoMeta->meta_title,
                    'meta_description' => $this->seoMeta->meta_description,
                    'og_image' => $this->seoMeta->og_image,
                    'canonical_url' => $this->seoMeta->canonical_url,
                ];
            }),
            'published_at' => $this->published_at,
        ];
    }
}
