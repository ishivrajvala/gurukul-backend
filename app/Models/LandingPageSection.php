<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageSection extends Model
{
    protected $fillable = [
        'landing_page_id',
        'type',
        'order',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }
}
