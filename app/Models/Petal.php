<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the Nine Petals — the human capacities the whole method is built on.
 *
 * Recorded against circles and stories so the mapping between a family's evening and the framework
 * is written down. NOT exposed as a browsing taxonomy: six parent-facing entry points map onto the
 * internal architecture, and showing a parent the whole architecture is what makes them feel they
 * have homework to do before speaking.
 */
class Petal extends Model
{
    protected $fillable = ['slug', 'name', 'number', 'intro'];

    protected function casts(): array
    {
        return ['number' => 'integer'];
    }

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('number');
    }
}
