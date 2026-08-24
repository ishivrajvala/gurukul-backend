<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * One email, written once and sent to the list.
 *
 * THE STATUS IS A LOCK, NOT A LABEL. `sending` is not there to be looked at — it is there so that
 * two people pressing Send at the same second, or a scheduler firing while somebody presses it by
 * hand, cannot both start the same campaign. `markSending()` claims the row with a conditional
 * UPDATE and the loser gets `false` back. Sending the same email twice is the one mistake in this
 * whole area that cannot be taken back.
 */
class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_SENDING => 'Sending',
        self::STATUS_SENT => 'Sent',
        self::STATUS_FAILED => 'Failed',
    ];

    protected $fillable = [
        'subject', 'preheader', 'content', 'status', 'scheduled_for', 'sent_at',
        'recipients_count', 'sent_count', 'failed_count', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Still editable. Once it is out, the words are somebody else's inbox and not ours to change. */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_FAILED], true);
    }

    public function isSendable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_FAILED], true);
    }

    /**
     * Claim this campaign for sending, or refuse.
     *
     * A CONDITIONAL UPDATE, NOT `if ($this->status === ...) { $this->save(); }`. Read-then-write has
     * a gap between the read and the write, and the whole job of this method is that two processes
     * cannot both pass through it. The database decides, and exactly one caller gets `true`.
     */
    public function markSending(): bool
    {
        $claimed = static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_FAILED])
            ->update(['status' => self::STATUS_SENDING, 'updated_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        $this->refresh();

        return true;
    }

    /** Recount from the delivery rows, which are the truth; the columns are only a cache. */
    public function refreshCounts(): void
    {
        $counts = $this->recipients()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $this->forceFill([
            'recipients_count' => (int) $counts->sum(),
            'sent_count' => (int) $counts->get(CampaignRecipient::STATUS_SENT, 0),
            'failed_count' => (int) $counts->get(CampaignRecipient::STATUS_FAILED, 0)
                + (int) $counts->get(CampaignRecipient::STATUS_BOUNCED, 0),
        ])->save();
    }

    /**
     * The email as HTML, exactly as it will arrive.
     *
     * THE PREVIEW AND THE SEND SHARE THIS, which is the whole point — a preview built from
     * different markup is a preview of nothing. The only difference is the logo: the mailable
     * embeds it as a CID attachment so it survives a client blocking remote images, and here it is
     * a plain URL because a browser can just fetch it.
     *
     * `$for` lets the preview show a real subscriber's unsubscribe link; without one it renders a
     * sample so the footer is never mysteriously empty.
     */
    public function renderHtml(?Subscriber $for = null): string
    {
        $subscriber = $for ?? new Subscriber([
            'name' => 'Sample Parent',
            'email' => 'parent@example.com',
            'unsubscribe_token' => Str::random(64),
        ]);

        return View::make('mail.campaign', [
            'subject' => $this->subject ?: 'Your subject line',
            'preheader' => $this->preheader,
            'body' => $this->content ?: '<p>Nothing written yet.</p>',
            'name' => $subscriber->name,
            'unsubscribeUrl' => $subscriber->unsubscribe_token
                ? $subscriber->unsubscribeUrl()
                : '#',
            'logoSrc' => asset('images/logo-white.webp'),
        ])->render();
    }

    /** How many deliverable addresses this would go to if sent now. */
    public function audienceSize(): int
    {
        return Subscriber::query()->deliverable()->count();
    }

    /** Campaigns whose time has come — see `campaigns:dispatch`. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now());
    }
}
