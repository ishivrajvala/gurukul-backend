<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the fifteen Parent Journal topics.
 *
 * OWNED BY THE JOURNAL ALONE. This was part of a single shared `topics` table that Circles, Stories
 * and Gatherings also filed against — see the split migration for why that stopped working. The
 * short version: this list is chosen for search, and Circles and Stories are not search surfaces.
 *
 * The list is chosen for what a parent actually types. "Focus & Behaviour" is a category because
 * parents search for concentration and focus; "STEAM" and "Gurukul Education" are categories
 * because they are where this Journal has something to say that nobody else does. The order is a
 * locked nav order and carries meaning, so `position`, never alphabetical.
 *
 * The frontend hard-codes the same fifteen with these exact slugs in `models/journalTopics.ts`,
 * which is what lets the API drop in behind the hand-written model rather than needing a
 * translation layer.
 */
class ArticleTopic extends Model
{
    protected $fillable = ['slug', 'name', 'full_name', 'eyebrow', 'description', 'position'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
