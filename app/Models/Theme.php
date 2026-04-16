<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'start_date',
        'end_date',
        'is_active',
        'is_home_active',
        'banner_text',
        'banner_cta_text',
        'banner_cta_link',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'is_home_active' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany('App\\Models\\Announcement');
    }

    public static function getActive(): ?self
    {
        $today = now()->toDateString();

        return self::query()
            ->where('status', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();
    }
}
