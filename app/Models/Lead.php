<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person waiting on a reply: waitlist, contact, call booking or parent guide.
 *
 * ONE TABLE FOR FOUR FORMS, because they differ by a couple of fields each and by nothing
 * structural, and four near-identical screens is four places to forget to look. Circle signups,
 * questions and story submissions stay separate because each carries a rule the schema itself has
 * to hold — a WhatsApp number, an anonymity flag, a reproducible consent — and folding those into
 * a `payload` blob would put a safeguarding record inside untyped JSON.
 *
 * WHAT IS NO LONGER HERE IS THE NEWSLETTER. A subscriber is not waiting on anybody; see
 * `Subscriber`, and the migration that split them for why one table could not hold both.
 *
 * THE PROCESS LIVES IN `LeadKind`, not here and not in the panel. This model knows how to ask.
 */
class Lead extends Model
{
    protected $fillable = [
        'kind', 'name', 'email', 'phone', 'age_stage_id', 'message', 'payload',
        'status', 'handled_at', 'handled_by',
        'offer_code', 'offer_applied_at', 'registered_at',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'first_source', 'first_campaign', 'landing_path', 'submitted_path', 'referrer',
        'click_id', 'click_platform', 'first_click_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'handled_at' => 'datetime',
            'offer_applied_at' => 'datetime',
            'registered_at' => 'datetime',
            'kind' => LeadKind::class,
        ];
    }

    public function ageStage(): BelongsTo
    {
        return $this->belongsTo(AgeStage::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * What has actually happened with this parent, newest first.
     *
     * The status says where a lead has got to; these say what was said. See `LeadNote`.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    /**
     * Record a contact, and move the lead in the same breath.
     *
     * ONE METHOD FOR BOTH, because they are one act. Somebody who has just rung a family should not
     * have to remember to also change a dropdown — and a status moved without a note is exactly the
     * gap this whole feature exists to close. The note carries the status it moved to, so the trail
     * explains itself later without holding two lists side by side.
     */
    public function recordContact(string $contact, string $body, ?string $status = null, ?int $userId = null): LeadNote
    {
        if ($status !== null && $status !== $this->status) {
            $this->moveTo($status, $userId);
        }

        return $this->notes()->create([
            'user_id' => $userId,
            'contact' => $contact,
            'body' => $body,
            'status_after' => $status,
        ]);
    }

    /** The statuses this row may take, which depend on what kind of lead it is. */
    /**
     * How this family found us, in one line, for a table column.
     *
     * FALLS BACK THROUGH THE CHAIN rather than showing an empty cell: a UTM campaign if there is
     * one, else the source, else the referring site, else "Direct". An attribution column that is
     * blank for half the rows gets ignored, and then the whole point of collecting it is lost.
     */
    public function sourceLabel(): string
    {
        if ($this->utm_campaign) {
            return $this->utm_source
                ? $this->utm_source.' · '.$this->utm_campaign
                : $this->utm_campaign;
        }

        if ($this->utm_source) {
            return $this->utm_source;
        }

        if ($this->referrer) {
            return (string) (parse_url($this->referrer, PHP_URL_HOST) ?: $this->referrer);
        }

        return 'Direct';
    }

    public function statusOptions(): array
    {
        return $this->kind?->statuses() ?? [];
    }

    public function statusLabel(): string
    {
        return $this->statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    /** True while this lead is still somebody's to chase — see `LeadKind::openStatuses`. */
    public function isOpen(): bool
    {
        return in_array($this->status, $this->kind?->openStatuses() ?? [], true);
    }

    /**
     * Move this lead to a status, stamping everything that follows from it.
     *
     * ONE PLACE FOR THE SIDE EFFECTS, used by the Advance button, the log-a-contact action and the
     * edit form alike. `registered_at` in particular has to be set by whichever path got there:
     * three copies of "if the status is now registered, also write the date" is three chances for
     * one of them to be forgotten, and a registration date that is right two thirds of the time is
     * worse than one that is empty, because it reads as fact.
     */
    public function moveTo(string $status, ?int $userId = null): void
    {
        $changes = [
            'status' => $status,
            'handled_at' => now(),
            'handled_by' => $userId,
        ];

        /* Only on the way IN, so re-saving a registered lead does not keep bumping the date. */
        if ($status === 'registered' && $this->registered_at === null) {
            $changes['registered_at'] = now();
        }

        $this->forceFill($changes)->save();
    }

    /**
     * Rows still waiting on a person, across every kind.
     *
     * BUILT PER KIND rather than as one `whereIn` over a shared list, because "open" means
     * different values for each: `scheduled` is open on a booking and does not exist on a contact
     * message. One flat list would count the wrong rows the moment two kinds shared a word.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            foreach (LeadKind::cases() as $kind) {
                $q->orWhere(function (Builder $inner) use ($kind): void {
                    $inner->where('kind', $kind->value)->whereIn('status', $kind->openStatuses());
                });
            }
        });
    }
}
