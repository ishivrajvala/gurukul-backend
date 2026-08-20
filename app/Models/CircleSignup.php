<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A parent asking to join a Circle, or to reserve a place at one gathering.
 *
 * NOTHING AUTO-ADMITS. `status` starts at `pending` and a person moves it, because the invite to a
 * WhatsApp group is sent by hand — which is what the confirmation copy promises the parent, and
 * what keeps a small held space held. An invite link that went out automatically would also be
 * forwardable to anybody.
 *
 * The WhatsApp number is stored AS GIVEN rather than normalised. Numbering plans differ by country
 * and change; a human reads every one of these before anybody is added, so the only validation
 * worth doing is catching a genuine slip.
 */
class CircleSignup extends Model
{
    protected $fillable = [
        'kind', 'circle_id', 'gathering_id',
        'name', 'email', 'whatsapp', 'age_stage_id', 'note',
        'status', 'handled_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function gathering(): BelongsTo
    {
        return $this->belongsTo(Gathering::class);
    }

    public function ageStage(): BelongsTo
    {
        return $this->belongsTo(AgeStage::class);
    }
}
