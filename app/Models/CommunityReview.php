<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A screenshot of something a parent said elsewhere.
 *
 * `publishable()` IS THE ONLY WAY THIS SHOULD BE READ. Google, Instagram and YouTube comments are
 * public; WhatsApp messages and emails are private, and republishing one without the family
 * agreeing is a breach whatever it says. The scope lives here so no controller can forget it.
 *
 * There is no rating and no total, and nothing here could produce "4.9 from 2,847 parents".
 */
class CommunityReview extends Model
{
    protected $fillable = [
        'source', 'image_path', 'image_alt', 'image_width', 'image_height',
        'has_permission', 'position',
    ];

    protected function casts(): array
    {
        return ['has_permission' => 'boolean'];
    }

    public function scopePublishable(Builder $query): Builder
    {
        return $query->where('has_permission', true)->orderBy('position');
    }
}
