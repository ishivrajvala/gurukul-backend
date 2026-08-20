<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SiteRoutes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A landing page: a slug, SEO, and an ordered list of sections.
 *
 * SLUGS THE SITE ALREADY OWNS ARE REFUSED. A landing page at `/contact` would be shadowed by the
 * real page for ever — Next resolves a static segment before a dynamic one — and nothing anywhere
 * would say why the page an editor published never appears. The list lives here rather than in a
 * database constraint because it is a fact about the frontend's routes, not about this table.
 *
 * Keep it in step with the site's `app/` directory. A slug missing from this list is a page that
 * silently never renders; a slug wrongly IN it is only a rejected name, which is the safer failure.
 */
class LandingPage extends Model
{
    protected $fillable = ['slug', 'title', 'is_published', 'published_at'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    /**
     * Routes the site already serves, from the one place that list lives.
     *
     * Kept as a constant-shaped accessor rather than a constant because it is derived: the same
     * list also fills the announcement CTA picker, and two copies would drift into a state where a
     * slug is bookable here AND offered as a destination there.
     */
    public static function reservedSlugs(): array
    {
        return SiteRoutes::reservedSlugs();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LandingSection::class)->orderBy('position');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metaable');
    }

    /**
     * Live pages.
     *
     * Both the flag AND a date that has passed: `published_at` in the future is a page scheduled
     * rather than a page live, which is the whole reason the column is separate from the boolean.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
