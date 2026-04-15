<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'content',
        'featured_image',
        'category_id',
        'theme_id',
        'is_featured',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag', 'blog_id', 'tag_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metaable');
    }
}
