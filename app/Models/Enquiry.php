<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The remaining site forms: waitlist, contact, newsletter, booking, parent guide, careers.
 *
 * ONE TABLE, because they differ by a couple of fields each and by nothing structural. Circle
 * signups, questions and story submissions are separate because each carries a rule the schema
 * itself has to hold — a WhatsApp number, an anonymity flag, a reproducible consent — and folding
 * those into a `payload` blob would put a safeguarding record inside untyped JSON.
 */
class Enquiry extends Model
{
    protected $fillable = [
        'kind', 'name', 'email', 'phone', 'age_stage_id', 'message', 'payload',
        'status', 'handled_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'handled_at' => 'datetime'];
    }

    public function ageStage(): BelongsTo
    {
        return $this->belongsTo(AgeStage::class);
    }
}
