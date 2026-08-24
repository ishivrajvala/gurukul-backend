<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Somebody on the weekly email list.
 *
 * NOT A LEAD, AND THE DIFFERENCE IS THE WHOLE REASON THIS CLASS EXISTS. A lead is answered once
 * and closed; the question about one is "has anybody dealt with this yet". A subscriber is written
 * to every week for years and is never dealt with — the only questions about one are whether they
 * still want it and whether the send still lands. Those are different columns, a different screen
 * and a different clock, and while the two shared a table the subscribers sat in the pending count
 * as work that could never be finished.
 *
 * THE PROCESS HERE IS THE SEND, not a queue: `SendWeeklyDigest` walks `deliverable()` once a week,
 * writes to each address and stamps `last_sent_at`. Everything below exists to serve that.
 */
class Subscriber extends Model
{
    /** Still wants the email, and it still arrives. */
    public const STATUS_SUBSCRIBED = 'subscribed';

    /** Asked to stop. Never written to again, and never deleted — see `unsubscribe()`. */
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /** The address rejected us. Not their decision, so it is not `unsubscribed`. */
    public const STATUS_BOUNCED = 'bounced';

    public const STATUSES = [
        self::STATUS_SUBSCRIBED => 'Subscribed',
        self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        self::STATUS_BOUNCED => 'Bounced',
    ];

    protected $fillable = [
        'email', 'name', 'status', 'source',
        'subscribed_at', 'unsubscribed_at', 'unsubscribe_token', 'last_sent_at',
        'bounced_at', 'bounce_reason',
        'utm_source', 'utm_campaign', 'landing_path',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    /**
     * Subscribe an address, or re-subscribe one that is already here.
     *
     * `updateOrCreate` ON THE EMAIL, because the box on the site has no idea who has used it
     * before. Somebody who subscribes twice must not get two copies of every weekly email, and
     * somebody who unsubscribed in March and comes back in July must actually come back rather
     * than hit a unique-constraint error the form would report as "something went wrong".
     *
     * The token is generated ONCE and kept across re-subscribes: an unsubscribe link in an old
     * email should still work, because the person clicking it wants out and a dead link means they
     * mark the next one as spam instead.
     */
    public static function subscribe(string $email, ?string $name = null, ?string $source = null): self
    {
        $subscriber = static::firstOrNew(['email' => mb_strtolower(trim($email))]);

        $subscriber->name = $name ?: $subscriber->name;
        $subscriber->source = $source ?: $subscriber->source;
        $subscriber->status = self::STATUS_SUBSCRIBED;
        $subscriber->subscribed_at = $subscriber->subscribed_at ?? now();
        $subscriber->unsubscribed_at = null;
        $subscriber->unsubscribe_token = $subscriber->unsubscribe_token ?: Str::random(64);
        $subscriber->save();

        return $subscriber;
    }

    /**
     * NEVER DELETES THE ROW. An unsubscribed address has to stay on file, because the record that
     * somebody asked to stop is the only thing that stops the next signup form, import or careless
     * re-seed from putting them back on the list. Deleting them forgets the request.
     */
    public function unsubscribe(): void
    {
        $this->forceFill([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ])->save();
    }

    /**
     * Who this week's email actually goes to.
     *
     * BOUNCED ADDRESSES ARE EXCLUDED HERE, not filtered out at send time. Continuing to write to
     * an address that has already rejected us is how a sending domain's reputation is lost, and
     * that failure is silent right up until the mail stops arriving for everybody else too.
     */
    public function scopeDeliverable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    /**
     * Checked again at the moment of sending, not only when a list is assembled.
     *
     * A long send takes time, and somebody who unsubscribes while it is running must not receive
     * the email already sitting in the queue behind them. `SendCampaignEmail` calls this last.
     */
    public function isDeliverable(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    /**
     * A provider told us this address is dead.
     *
     * ONLY A BOUNCE WEBHOOK MAY CALL THIS. An SMTP error at send time is not a bounce — a refused
     * connection, a timeout or a rate limit are all our problem, and marking somebody's address
     * dead because our mail server had a bad afternoon quietly shrinks the list for a reason that
     * has nothing to do with them. A real bounce arrives afterwards, from the provider.
     *
     * The row is kept, as with an unsubscribe: `deliverable()` already excludes it, and the record
     * of WHY is the only thing that stops it being re-added and bounced again next week.
     */
    public function markBounced(?string $reason = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'bounce_reason' => $reason ? mb_substr($reason, 0, 255) : null,
        ])->save();
    }

    public function unsubscribeUrl(): string
    {
        return route('subscribers.unsubscribe', ['token' => $this->unsubscribe_token]);
    }
}
