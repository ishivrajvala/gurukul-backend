<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A public profile. See the migration for why every platform exists as a row from the start.
 */
class SocialLink extends Model
{
    protected $fillable = ['platform', 'label', 'url', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    /**
     * Only the ones actually in use.
     *
     * `whereNotNull` IS NOT ENOUGH. A text input that has been typed into and cleared saves an empty
     * string, not null, and an empty string is not null — so the row would pass and the site would
     * render an icon linking to nowhere. Both cases mean the same thing to a person and must mean
     * the same thing here.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNotNull('url')->where('url', '!=', '')->orderBy('sort_order');
    }
}
