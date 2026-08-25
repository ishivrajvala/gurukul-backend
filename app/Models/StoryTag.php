<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What a Parent Story is about.
 *
 * TAGS, NOT TOPICS, and the difference is the whole reason this table exists. A Journal topic
 * answers "what subject is this filed under". A story tag answers "what changed in this family" —
 * "Letting go of control", "Learning without pressure". The stories were previously filed against
 * the same subject vocabulary as articles, which meant a story about a parent who stopped checking
 * homework was labelled "Learning", as though it were an article about reading levels.
 *
 * This is NOT the story's eyebrow. `Story::eyebrow` is what the site prints above the title and is
 * a different question again — what the story turns out to be about developmentally. A tag groups;
 * an eyebrow describes. Both exist on purpose.
 */
class StoryTag extends Model
{
    protected $fillable = ['slug', 'name', 'full_name', 'eyebrow', 'description', 'position'];

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
