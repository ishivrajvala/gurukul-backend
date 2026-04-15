<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class LandingPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'theme_id',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LandingPageSection::class)->orderBy('order');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metaable');
    }
}
