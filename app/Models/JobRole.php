<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An open role.
 *
 * `job_roles`, not `roles`: that name belongs to spatie/laravel-permission in this database, and a
 * vacancy landing in it would become an assignable permission.
 *
 * IT IS ALSO A JobPosting. The careers page emits structured data from these rows, so `location`,
 * `commitment` and `date_posted` reach job boards as fact rather than as page copy. A wrong value
 * here is a wrong value in Google's job listings.
 *
 * CLOSE A ROLE, DO NOT DELETE IT. `is_published` takes it off the site while keeping the
 * applications attached to something that says what they were for; the foreign key restricts the
 * delete precisely so that closing is the path of least resistance.
 */
class JobRole extends Model
{
    protected $fillable = [
        'slug', 'title', 'team', 'location', 'commitment', 'openings',
        'experience', 'date_posted', 'summary', 'responsibilities', 'looking',
        'is_published', 'position',
    ];

    protected function casts(): array
    {
        return [
            'responsibilities' => 'array',
            'looking' => 'array',
            'is_published' => 'boolean',
            'openings' => 'integer',
            'position' => 'integer',
            'date_posted' => 'date',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Live on the site, in the editor's order. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
