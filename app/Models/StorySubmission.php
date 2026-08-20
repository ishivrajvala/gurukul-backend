<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A family offering their story.
 *
 * NOTHING AUTO-PUBLISHES. `status` starts at `pending`; the form tells the parent every submission
 * is reviewed before publication, and that promise is the reason many of them submit at all.
 *
 * `consent_text` STORES THE EXACT SENTENCE AGREED TO, not a boolean alone. Avdhara's framework
 * requires explicit family consent for identifiable child stories and images; a consent you cannot
 * reproduce is not one you can rely on months later when the wording has changed. The child clause
 * lives inside that sentence rather than in a separate tick, so it cannot be agreed to by accident.
 */
class StorySubmission extends Model
{
    protected $fillable = [
        'name', 'email', 'age_stage_id', 'story',
        'video_path', 'photo_path',
        'has_consent', 'consent_text',
        'status', 'story_id', 'handled_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return ['has_consent' => 'boolean', 'handled_at' => 'datetime'];
    }

    public function ageStage(): BelongsTo
    {
        return $this->belongsTo(AgeStage::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
