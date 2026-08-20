<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One of the three parent concerns on the homepage — "Screens are taking over childhood."
 *
 * WRITTEN AS THE PARENT WOULD SAY IT, in the first person and without a solution attached. The
 * whole band is called "You are not alone"; a card that opens by selling would break the one thing
 * it is for. The link at the foot is where the answer lives.
 *
 * NO COLOURS HERE. First card indigo, second marigold, third green — a locked palette decision the
 * view makes from `position`. An editor picking a hex is how six brand colours become twelve.
 */
class HomeConcern extends Model
{
    protected $fillable = [
        'eyebrow', 'title', 'bullets', 'cta', 'cta_href',
        'icon', 'image_path', 'image_alt', 'is_published', 'position',
    ];

    protected function casts(): array
    {
        return ['bullets' => 'array', 'is_published' => 'boolean', 'position' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
