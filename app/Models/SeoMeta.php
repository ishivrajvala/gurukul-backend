<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
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
