<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Parent Circle: a standing, moderated space.
 *
 * A CIRCLE IS A PLACE, NOT AN EVENT. It is joinable whether or not anything is scheduled, which is
 * why joining it and reserving a gathering are two different actions with two different records.
 *
 * NO ENGAGEMENT COLUMNS, and none may be added: no member count, no activity, no popularity, no
 * threads. `community.ts` locks the attention-as-sacred rule, and a circle with a temperature
 * reading on it stops being a held space.
 */
class Circle extends Model
{
    protected $fillable = [
        'slug', 'name', 'purpose', 'description',
        'managed_by_avdhara', 'is_featured', 'is_published', 'position',
    ];

    protected function casts(): array
    {
        return [
            'managed_by_avdhara' => 'boolean',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function ageStages(): BelongsToMany
    {
        return $this->belongsToMany(AgeStage::class);
    }

    public function circleTopics(): BelongsToMany
    {
        return $this->belongsToMany(CircleTopic::class);
    }

    public function petals(): BelongsToMany
    {
        return $this->belongsToMany(Petal::class);
    }

    public function gatherings(): HasMany
    {
        return $this->hasMany(Gathering::class);
    }

    /** "All Ages" when no stage is attached: the circle is for every stage, not for none. */
    public function audienceLabel(): string
    {
        $stages = $this->ageStages;

        if ($stages->isEmpty()) {
            return 'All Ages';
        }

        return 'Ages ' . $stages->min('age_from') . '\u{2013}' . $stages->max('age_to');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
