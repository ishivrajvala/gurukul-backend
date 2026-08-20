<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * What a search engine shows, when it should differ from what the page shows.
 *
 * POLYMORPHIC because the question is the same for an article, a story and a circle, and three
 * near-identical sets of columns on three tables is three places to forget one. Today only
 * `Article` attaches one; the others need a relation and a form section, not a schema change.
 *
 * EVERY FIELD IS OPTIONAL AND EVERY FIELD FALLS BACK. A missing row is the normal state, not a gap
 * to fill: most articles are best described by their own headline, and an editor should write one
 * of these when the page title is genuinely the wrong thing to show in a result list.
 */
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'metaable_type',
        'metaable_id',
        'meta_title',
        'meta_description',
        'og_image',
        'canonical_url',
    ];

    public function metaable(): MorphTo
    {
        return $this->morphTo();
    }
}
