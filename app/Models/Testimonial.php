<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A short reflection from a parent. NOT a story.
 *
 * The distinction is why two tables exist: a story is a narrative with a page of its own, a
 * testimonial is a sentence with nowhere further to go. Collapsing them gives you either
 * testimonials padded out to look like articles or stories flattened into pull quotes.
 *
 * ATTRIBUTION IS THE PATHWAY FAMILY — "Explorer family" — never a full name and never a photograph.
 * These are the least-contextualised things on the page, so they carry the least identifying
 * detail: a sentence with a stranger's face attached invites a reader to weigh the person rather
 * than hear the words.
 *
 * `content` holds the quote. Kept rather than renamed to `quote`: the column predates this module
 * and renaming it buys a nicer name at the cost of a migration nothing else needs.
 */
class Testimonial extends Model
{
    protected $fillable = [
        'slug', 'content', 'family_label', 'position', 'is_published',
        'type', 'title', 'name', 'tags', 'video_url', 'thumbnail', 'is_featured', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
