<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One term under the Journal search field.
 *
 * EDITORIAL, NOT MEASURED. Nothing counts what visitors actually search for, and this table is not
 * a leaderboard pretending otherwise — it is a short list of ways in, chosen by a person. Calling
 * it "trending" is the site's word for "start here", and it is deliberately six or so terms rather
 * than a ranked twenty.
 *
 * A TERM MUST FIND SOMETHING. Pressing one runs the ordinary search over the article feed, so a
 * term that matches nothing is a button that empties the page. There is no constraint that can
 * enforce that from here — the search is client-side over titles and standfirsts — which is why the
 * admin screen says so plainly instead.
 */
class TrendingSearch extends Model
{
    protected $fillable = ['term', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
