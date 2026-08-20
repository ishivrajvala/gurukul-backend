<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One of the six developmental stages, youngest first.
 *
 * The names are the pathway names the site already routes on (/seekers … /visionaries). Design
 * comps have variously called 8–11 "Connectors" and 14–16 "Pathfinders"; those would rename live
 * pathways, so the locked names win here too.
 */
class AgeStage extends Model
{
    protected $fillable = ['key', 'name', 'range_label', 'age_from', 'age_to', 'position'];

    protected function casts(): array
    {
        return ['age_from' => 'integer', 'age_to' => 'integer', 'position' => 'integer'];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class);
    }

    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
