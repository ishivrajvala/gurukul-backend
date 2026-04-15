<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'type',
        'title',
        'name',
        'tags',
        'content',
        'video_url',
        'thumbnail',
        'theme_id',
        'is_featured',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
