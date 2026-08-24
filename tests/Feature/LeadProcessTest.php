<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadKind;
use Tests\TestCase;

/**
 * The lead lifecycle, its notes trail, and the waitlist's offer.
 *
 * These pin behaviour that is invisible from a status code: a note written without its status, a
 * registration date stamped by one path and not another, or an offer field appearing on a kind that
 * has nothing to offer.
 */
class LeadProcessTest extends TestCase
{
    private array $made = [];

    protected function tearDown(): void
    {
        Lead::whereIn('id', $this->made)->each(fn (Lead $l) => $l->notes()->delete());
        Lead::whereIn('id', $this->made)->delete();

        parent::tearDown();
    }

    private function lead(string $kind): Lead
    {
        $lead = Lead::create([
            'kind' => $kind,
            'name' => 'Process Test',
            'email' => 'process-test@example.invalid',
            'status' => LeadKind::from($kind)->initialStatus(),
        ]);

        $this->made[] = $lead->id;

        return $lead;
    }

    /** A note and a status move are one act, and the note remembers which move it caused. */
    public function test_logging_a_contact_records_the_note_and_moves_the_lead(): void
    {
        $lead = $this->lead('booking');

        $lead->recordContact('called', 'Rang, agreed Tuesday.', 'scheduled', null);

        $this->assertSame('scheduled', $lead->fresh()->status);
        $this->assertSame(1, $lead->notes()->count());
        $this->assertSame('scheduled', $lead->notes()->first()->status_after);
        $this->assertNotNull($lead->fresh()->handled_at, 'moving a lead must stamp when');
    }

    /** A call that changed nothing is still worth recording, and must not invent a move. */
    public function test_a_note_without_a_status_change_leaves_the_status_alone(): void
    {
        $lead = $this->lead('contact');

        $lead->recordContact('no_answer', 'No answer, will try tomorrow.', 'new', null);

        $this->assertSame('new', $lead->fresh()->status);
        $this->assertSame(1, $lead->notes()->count());
    }

    /**
     * `registered_at` IS STAMPED BY WHICHEVER PATH GETS THERE — the Advance button, a logged call,
     * or the edit form. That is why the side effect lives in `moveTo` and not in the panel.
     */
    public function test_reaching_registered_stamps_the_date_once(): void
    {
        $lead = $this->lead('waitlist');

        $lead->moveTo('invited');
        $this->assertNull($lead->fresh()->registered_at);

        $lead->moveTo('registered');
        $first = $lead->fresh()->registered_at;
        $this->assertNotNull($first, 'registered_at was not stamped');

        /* Re-saving must not keep bumping it — the date is when they registered, not when we looked. */
        $lead->fresh()->moveTo('registered');
        $this->assertEquals($first, $lead->fresh()->registered_at);
    }

    /** The waitlist gained a step between invited and joined, and it counts as still open. */
    public function test_the_waitlist_process_includes_registered_and_it_is_open(): void
    {
        $waitlist = LeadKind::Waitlist;

        $this->assertSame(
            ['new', 'invited', 'registered', 'joined', 'declined'],
            array_keys($waitlist->statuses()),
        );
        $this->assertContains('registered', $waitlist->openStatuses());
        $this->assertSame('registered', $waitlist->nextStatus('invited'));
    }

    /** Only the waitlist takes an offer. Nothing else has anything to move a family toward yet. */
    public function test_only_the_waitlist_takes_an_offer(): void
    {
        $this->assertTrue(LeadKind::Waitlist->takesOffer());
        $this->assertFalse(LeadKind::Contact->takesOffer());
        $this->assertFalse(LeadKind::Booking->takesOffer());
        $this->assertFalse(LeadKind::ParentGuide->takesOffer());
    }

    /** Advance never hands back a terminal outcome — those must be chosen deliberately. */
    public function test_advance_refuses_to_reach_a_terminal_outcome(): void
    {
        $this->assertNull(LeadKind::Booking->nextStatus('completed'), 'must not advance into no_show');
        $this->assertNull(LeadKind::Waitlist->nextStatus('joined'), 'must not advance into declined');
        $this->assertNull(LeadKind::Contact->nextStatus('answered'), 'must not advance into closed');
    }
}
