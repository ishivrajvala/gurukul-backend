<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question brought to the Circle.
 *
 * `is_anonymous` MEANS ANONYMOUS TO OTHER PARENTS, not to Avdhara. Circles receive questions about
 * neurodivergence, developmental worry, family difficulty, illness and grief, and a parent who has
 * to attach their name mostly does not ask. The moderator still sees the row — that is the
 * difference between a held space and an unattended one, and the hint under the control says so in
 * as many words rather than letting a parent assume more privacy than they have.
 *
 * A facilitator relaying an anonymous question strips the name before it reaches the group. Nothing
 * here posts anywhere by itself.
 */
class CircleQuestion extends Model
{
    protected $fillable = [
        'circle_id', 'question', 'is_anonymous', 'name', 'email',
        'status', 'handled_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['is_anonymous' => 'boolean', 'handled_at' => 'datetime'];
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }
}
