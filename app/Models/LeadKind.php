<?php

declare(strict_types=1);

namespace App\Models;

/**
 * The four lead kinds, and the process each one actually follows.
 *
 * THIS IS THE MODEL LAYER, and it is deliberately the only place any of this is written down. The
 * panel used to offer one status dropdown — `pending / handled / declined` — to all of them, which
 * is the only vocabulary four different processes could agree on and describes none of them. A
 * booking that has been scheduled is neither pending nor handled. A contact message that has been
 * replied to but not resolved has nowhere to sit. "Handled" on a call booking does not say whether
 * anybody turned up, which is the one thing worth recording about a call.
 *
 * So each kind carries its own statuses here, and the resource, the tabs, the badges and the row
 * actions all read them from this enum. Adding a step to the booking process is a line in
 * `statuses()`; nothing in the view changes, and nothing in the view may hard-code a status.
 *
 * NEWSLETTER IS NOT HERE. A subscriber is not a lead — nobody handles one, there is nothing to do
 * about one, and it is on a weekly clock rather than a reply-once clock. See `Subscriber`.
 */
enum LeadKind: string
{
    case Waitlist = 'waitlist';
    case Contact = 'contact';
    case Booking = 'booking';
    case ParentGuide = 'parent-guide';

    public function label(): string
    {
        return match ($this) {
            self::Waitlist => 'Join waitlist',
            self::Contact => 'Contact',
            self::Booking => 'Book a call',
            self::ParentGuide => 'Parent guide',
        };
    }

    /**
     * The workflow, in order, as `value => label`.
     *
     * ORDER IS THE PROCESS. The first entry is where a lead starts and the ones after it are the
     * steps in the order they happen, so a dropdown reads as a path rather than as a bag of
     * options. Terminal states come last.
     */
    public function statuses(): array
    {
        return match ($this) {
            /*
             * WAITLIST: a place is offered, then taken or not. "Invited" is the step that used to
             * be missing entirely — a family who has been offered a place and has not answered yet
             * is the single most important state on this list, and under the old vocabulary it was
             * indistinguishable from one nobody had contacted.
             */
            self::Waitlist => [
                'new' => 'New',
                'invited' => 'Invited',
                /*
                 * REGISTERED SITS BETWEEN INVITED AND JOINED, and its absence was hiding the most
                 * important week of this process. A family invited a month ago and one who has
                 * signed up and is waiting to start looked identical — both `invited` — so the
                 * chasing list and the welcoming list were the same list.
                 */
                'registered' => 'Registered',
                'joined' => 'Joined',
                'declined' => 'Declined',
            ],

            /*
             * CONTACT: a message is answered, then closed. "Replying" exists because these are
             * conversations — a thread that is mid-exchange is not waiting on us and must not sit
             * in the same count as one nobody has read.
             */
            self::Contact => [
                'new' => 'New',
                'replying' => 'Replying',
                'answered' => 'Answered',
                'closed' => 'Closed',
            ],

            /*
             * BOOKING: the only kind with a future event in it, which is why it needs the most
             * states. A booked call is not finished work, and whether somebody turned up is a fact
             * about a person the next call should know — "handled" threw both away.
             */
            self::Booking => [
                'new' => 'New',
                'scheduled' => 'Scheduled',
                'completed' => 'Completed',
                'no_show' => 'No show',
                'cancelled' => 'Cancelled',
            ],

            /*
             * PARENT GUIDE: one email with one attachment. It is nearly automatic, so it gets the
             * shortest process of the four — `failed` is here only because an address that bounces
             * is the one outcome somebody has to see.
             */
            self::ParentGuide => [
                'new' => 'New',
                'sent' => 'Sent',
                'failed' => 'Failed',
            ],
        };
    }

    /**
     * Whether an offer can be attached to this kind.
     *
     * WAITLIST ONLY, and deliberately. An offer is something given to move a family from interest
     * to registration; a contact message or a guide request has nothing to move somebody toward
     * yet, and an offer field on those is an invitation to discount for no reason. The site's
     * positioning depends on offers adding value rather than cutting price — see RUNBOOK.md.
     */
    public function takesOffer(): bool
    {
        return $this === self::Waitlist;
    }

    /** Where a lead of this kind starts. Every process happens to begin at `new`. */
    public function initialStatus(): string
    {
        return array_key_first($this->statuses());
    }

    /**
     * The statuses that still need somebody, which is what the inbox counts.
     *
     * NOT SIMPLY "NOT THE LAST ONE". An invited family and a scheduled call are both mid-process
     * and both still ours to chase; a declined one is not. A badge that counts everything unfinished
     * climbs for ever and gets ignored, and a badge that counts only untouched rows hides the
     * follow-ups — this is the line between the two.
     */
    public function openStatuses(): array
    {
        return match ($this) {
            /* Registered is still ours: they have signed up and not yet started. */
            self::Waitlist => ['new', 'invited', 'registered'],
            self::Contact => ['new', 'replying'],
            self::Booking => ['new', 'scheduled'],
            self::ParentGuide => ['new'],
        };
    }

    /** The next step, for the one-click action on a table row. Null at a terminal status. */
    public function nextStatus(string $current): ?string
    {
        $keys = array_keys($this->statuses());
        $at = array_search($current, $keys, true);

        if ($at === false || ! isset($keys[$at + 1])) {
            return null;
        }

        /*
         * Only ever advances along the happy path. `no_show`, `cancelled`, `declined` and `failed`
         * are outcomes somebody has to choose deliberately, never one click from the row — marking
         * a family as having declined by accident is not a mistake you find out about.
         */
        $next = $keys[$at + 1];

        return in_array($next, ['declined', 'closed', 'no_show', 'cancelled', 'failed'], true)
            ? null
            : $next;
    }

    /** Badge colour, from the six brand tokens Filament's palette exposes. */
    public function statusColour(string $status): string
    {
        return match ($status) {
            'new' => 'warning',
            'joined', 'answered', 'completed', 'sent' => 'success',
            'registered' => 'success',
            'declined', 'no_show', 'cancelled', 'failed' => 'danger',
            default => 'primary',
        };
    }

    /** Where this kind comes from on the site, for the empty state. */
    public function source(): string
    {
        return match ($this) {
            self::Waitlist => 'Waitlist signups arrive here, from the Join Waitlist button in the site footer and the waitlist form.',
            self::Contact => 'Messages arrive here, from the form on the Contact page.',
            self::Booking => 'Call requests arrive here, from Book a call in the site navigation. The time is not held until somebody replies.',
            self::ParentGuide => 'Requests arrive here, from the Get the guide form at the foot of the homepage.',
        };
    }

    public function emptyHeading(): string
    {
        return match ($this) {
            self::Waitlist => 'Nobody on the waitlist yet',
            self::Contact => 'No messages yet',
            self::Booking => 'No calls requested yet',
            self::ParentGuide => 'Nobody has asked for the guide yet',
        };
    }

    /** `value => label`, for filters and tabs. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $kind): array => $carry + [$kind->value => $kind->label()],
            [],
        );
    }

    /**
     * Every status any kind uses, deduplicated — for the one filter that spans all four tabs.
     *
     * A cross-kind status filter is a slightly odd thing by construction: "scheduled" only means
     * something on a booking. It is here because the All tab needs one, and the alternative — no
     * status filter at all until you pick a kind — is worse.
     */
    public static function allStatusOptions(): array
    {
        $all = [];

        foreach (self::cases() as $kind) {
            $all += $kind->statuses();
        }

        return $all;
    }
}
