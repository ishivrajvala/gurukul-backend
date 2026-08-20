<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A Parent Journal article. Formerly `Blog`.
 *
 * The rename is not cosmetic: `models/journal-content.ts` on the frontend already documents this
 * table as the Journal's store, and every other file in both projects says article or journal. One
 * word, everywhere.
 *
 * `content` IS EDITOR HTML, from `awcodes/filament-tiptap-editor` with `TiptapOutput::Html` — which
 * is why the frontend has a `sanitizeEditorHtml` step and renders it through exactly one component.
 * That is the one real difference from a Story, whose body is plain paragraphs.
 *
 * TITLES ARE THE QUESTIONS PARENTS ACTUALLY TYPE. That is the whole taxonomy decision: a parent
 * arrives with "should my five-year-old be reading", not with "reading readiness", so the piece is
 * named after the question and the topic is the quiet label above it.
 */
class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'standfirst', 'short_description', 'content',
        'featured_image', 'featured_image_alt',
        'topic_id', 'category_id',
        'reading_minutes', 'is_featured', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'reading_minutes' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function ageStages(): BelongsToMany
    {
        return $this->belongsToMany(AgeStage::class);
    }

    /**
     * Published means it has copy AND a date that has passed.
     *
     * A row without copy is commissioned but unwritten, which the frontend treats as a real state:
     * the reading page says so and the route puts `noindex` on it, rather than padding it out with
     * filler that would read as an article and be indexed as one.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    /**
     * Editable search-result metadata.
     *
     * WITHOUT IT, GOOGLE SHOWS THE HEADLINE AND THE STANDFIRST, and those are written for somebody
     * who has already arrived. "Should My Five-Year-Old Already Be Reading?" is a fine title on the
     * page and a poor one in a result list next to nine competitors; the standfirst is a lead-in,
     * not a 155-character summary that has to work alone.
     *
     * Every field is optional and every field falls back, so an article with no row here behaves
     * exactly as it did before. `seo_meta` has existed since the first schema and was used by
     * nothing at all until now.
     */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metaable');
    }
}
