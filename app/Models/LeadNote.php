<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing that happened with one parent.
 *
 * THE STATUS SAYS WHERE, THIS SAYS WHAT. "Completed" is the same word whether the call went well or
 * the parent said the timing is wrong and to try again in March — and the second is the only
 * version worth knowing before anybody rings them back.
 *
 * APPEND ONLY. Nothing here is edited or deleted from the panel: a trail you can revise is a trail
 * you cannot trust, and the one time it matters is the one time somebody is reconstructing what was
 * actually said to a family.
 */
class LeadNote extends Model
{
    /** How the contact happened. `note` is the default: something worth recording, no contact made. */
    public const CONTACTS = [
        'note' => 'Note',
        'called' => 'Called',
        'no_answer' => 'Called — no answer',
        'emailed' => 'Emailed',
        'messaged' => 'WhatsApp / message',
        'met' => 'Met',
    ];

    protected $fillable = ['lead_id', 'user_id', 'contact', 'body', 'status_after'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contactLabel(): string
    {
        return self::CONTACTS[$this->contact] ?? $this->contact;
    }
}
