<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What a Parent Circle, and each of its gatherings, is about.
 *
 * THIS IS THE ORIGINAL TEN, kept. When the shared `topics` table was split three ways, the Journal
 * took a new fifteen chosen for search and Stories were re-tagged around outcomes — but the ten
 * were designed for exactly this job in the first place, and all six circles already sit across
 * them cleanly. Changing them would have been churn for its own sake.
 *
 * A circle carries SEVERAL of these (a circle about screens is also about attention and safety), so
 * that side is many-to-many. A gathering carries ONE, and it sits on the gathering rather than
 * being inherited from the circle — see GatheringResource for why that is deliberate.
 */
class CircleTopic extends Model
{
    protected $fillable = ['slug', 'name', 'full_name', 'eyebrow', 'description', 'position'];

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class);
    }

    public function gatherings(): HasMany
    {
        return $this->hasMany(Gathering::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
