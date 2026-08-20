<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Somebody applying for a role.
 *
 * ITS OWN TABLE, not an `enquiry` with a JSON payload. It carries three things the schema has to
 * hold: the role as a foreign key, a CV that is required rather than optional, and a portfolio link
 * that several of these roles are actually judged on. It also deserves its own list — an
 * application interleaved with newsletter signups is an application nobody reads in time.
 *
 * `cv_path` IS ON THE PRIVATE DISK. No URL reaches it. The admin download action is the only way to
 * read one, so the panel session is the authorisation rather than a filename nobody can guess.
 */
class JobApplication extends Model
{
    protected $fillable = [
        'job_role_id', 'name', 'email', 'phone', 'portfolio_url',
        'note', 'cv_path', 'status', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(JobRole::class, 'job_role_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
