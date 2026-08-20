<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the ten shared topics.
 *
 * SHARED IS THE WHOLE POINT. Journal articles, Circles, gatherings and Stories all file against
 * this one list, in this one order. The frontend hard-codes the same ten in `models/topics.ts` with
 * these exact slugs, which is what lets the API drop in behind the hand-written model rather than
 * needing a translation layer.
 *
 * Ordered by `position`, never alphabetically: the order is a locked nav order and carries meaning.
 */
class Topic extends Model
{
    protected $fillable = ['slug', 'name', 'full_name', 'eyebrow', 'description', 'position'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
