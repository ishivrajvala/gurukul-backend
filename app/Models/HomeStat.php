<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One figure in the homepage's "You are not alone" band.
 *
 * A CLAIM ABOUT THE WORLD, not about Avdhara. "1 in 3 children show signs of attention difficulty
 * by age 8" is research; it is not a rating, a follower count or a number of families, which is why
 * this band sits outside the site's no-counts rule while the review wall sits inside it.
 *
 * That distinction only holds while the figures are true, so `source` is where the person writing
 * one records where it came from. Nothing renders it — it is a note to the next editor.
 */
class HomeStat extends Model
{
    protected $fillable = ['figure', 'description', 'icon', 'source', 'is_published', 'position'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'position' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
