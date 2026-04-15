<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Blog */
class BlogIndexResource extends JsonResource
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
            'published_at' => $this->published_at,
        ];
    }
}
