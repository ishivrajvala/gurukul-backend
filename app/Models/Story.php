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
        'story_tag_id', 'petal_id',
        'author_name', 'author_relation', 'place',
        'media_kind', 'youtube_id', 'media_aspect', 'duration_minutes',
        'reading_minutes', 'image_path', 'image_alt',
        'is_featured', 'home_position', 'is_published', 'published_at',
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

    public function storyTag(): BelongsTo
    {
        return $this->belongsTo(StoryTag::class);
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

    /**
     * The homepage band: the chosen stories, the lead first.
     *
     * SEPARATE FROM `is_featured` ON PURPOSE. That flag drives the Parent Stories carousel, and
     * while the homepage read it too the two surfaces could not differ — promoting a story on the
     * hub promoted it on the homepage as a side effect, and nobody could say which one led.
     *
     * `home_position` 1 is the lead, 2 to 5 the row beneath, null is not on the homepage at all.
     */
    public function scopeOnHome(Builder $query): Builder
    {
        /*
         * `reorder()` FIRST, and it is the whole reason this works.
         *
         * `published()` already applied `orderByDesc('published_at')`, and a second `orderBy` only
         * ever becomes a TIEBREAKER behind the first. Chaining `published()->onHome()` therefore
         * returned the right five stories in publication order while looking exactly like a working
         * sort — an editor moving a story to slot 1 saw nothing change and no error anywhere.
         */
        return $query->whereNotNull('home_position')->reorder()->orderBy('home_position');
    }
}
