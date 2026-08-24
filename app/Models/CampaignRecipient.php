<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person, one campaign, one delivery record.
 *
 * THIS IS WHERE "DID IT ARRIVE" IS ANSWERED. The counters on `Campaign` can say four failed; only
 * these rows can say which four, on what grounds, and when — and only these let a bounce that
 * arrives days later be traced back to the send that caused it. Everything on the campaign is a
 * cache of what is here.
 */
class CampaignRecipient extends Model
{
    /** Handed to the mailer, not yet accepted by anything. */
    public const STATUS_QUEUED = 'queued';

    /** Accepted by the mail server. NOT the same as delivered — see the note on bounces below. */
    public const STATUS_SENT = 'sent';

    /** Rejected at send time, synchronously. The address never left the building. */
    public const STATUS_FAILED = 'failed';

    /**
     * Rejected AFTER acceptance, reported back later by the mail provider.
     *
     * This is the one that cannot be detected locally. SMTP accepts a message and only afterwards
     * discovers the mailbox does not exist, so a bounce arrives minutes or hours later as a webhook
     * from the provider — see `routes/web.php` and RUNBOOK.md. Without that webhook wired up, dead
     * addresses stay marked `sent` for ever and the list quietly rots.
     */
    public const STATUS_BOUNCED = 'bounced';

    /** They pressed "this is spam". Worse than an unsubscribe and treated as harder. */
    public const STATUS_COMPLAINED = 'complained';

    public const STATUSES = [
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_BOUNCED => 'Bounced',
        self::STATUS_COMPLAINED => 'Complained',
    ];

    protected $fillable = [
        'campaign_id', 'subscriber_id', 'email', 'status', 'error', 'sent_at', 'bounced_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'bounced_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
