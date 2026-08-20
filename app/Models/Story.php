<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A Parent Story: written, or a video.
 *
 * ONE CONTENT TYPE, TWO SHAPES. `media_kind` is the only field that differs, because a parent
 * browsing should experience a filmed story and a written one as two shapes of the same thing.
 *
 * `body` is an array of plain paragraphs, never HTML. A parent story is somebody talking, so there
 * is no markup to render and therefore none to sanitise — which is the one real difference from a
 * Journal article, whose body IS editor HTML.
 */
class Story extends Model
{
    protected $fillable = [
        'slug', 'title', 'standfirst', 'body',
        'topic_id', 'petal_id',
        'author_name', 'author_relation', 'place',
        'media_kind', 'youtube_id', 'media_aspect', 'duration_minutes',
        'reading_minutes', 'image_path', 'image_alt',
        'is_featured', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'duration_minutes' => 'integer',
            'reading_minutes' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function petal(): BelongsTo
    {
        return $this->belongsTo(Petal::class);
    }

    public function ageStages(): BelongsToMany
    {
        return $this->belongsToMany(AgeStage::class);
    }

    public function isVideo(): bool
    {
        return $this->media_kind === 'video';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }
}
