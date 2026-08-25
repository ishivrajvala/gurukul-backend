<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One facilitated hour inside a Circle.
 *
 * AVAILABILITY IS A BAND, NOT A COUNT. `capacity` and `taken` are here because a facilitator needs
 * them and the CMS will, but the API returns `availability` as one of three phrases. The locked
 * rule bans enrollment counts, and a small facilitated group is exactly the thing that should not
 * be presented as a filling scoreboard.
 */
class Gathering extends Model
{
    protected $fillable = [
        'slug', 'circle_id', 'title', 'description', 'circle_topic_id',
        'starts_at', 'format', 'city', 'capacity', 'taken', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
            'taken' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function circleTopic(): BelongsTo
    {
        return $this->belongsTo(CircleTopic::class);
    }

    public function ageStages(): BelongsToMany
    {
        return $this->belongsToMany(AgeStage::class);
    }

    /** Three bands rather than a number, so a parent learns whether to hurry without a scoreboard. */
    public function availabilityLabel(): string
    {
        $left = $this->capacity - $this->taken;

        if ($left <= 0) {
            return 'Fully booked';
        }

        return $left <= $this->capacity * 0.34 ? 'A few places left' : 'Places available';
    }

    /** The city for an in-person gathering; "In person" alone raises the question it fails to answer. */
    public function formatLabel(): string
    {
        return $this->format === 'online' ? 'Online' : ($this->city ?? 'In person');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now())->orderByDesc('starts_at');
    }
}
