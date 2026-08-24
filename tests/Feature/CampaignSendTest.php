<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use App\Support\CampaignDispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The send path, which had never been run end to end.
 *
 * Everything here exists because sending the same email twice is the one mistake in this
 * application that cannot be taken back, and because a delivery record that lies is worse than no
 * record at all.
 */
class CampaignSendTest extends TestCase
{
    private array $made = [];

    protected function tearDown(): void
    {
        CampaignRecipient::whereIn('campaign_id', $this->made)->delete();
        Campaign::whereIn('id', $this->made)->delete();
        Subscriber::where('email', 'like', '%@campaign-test.invalid')->delete();

        parent::tearDown();
    }

    private function campaign(): Campaign
    {
        $c = Campaign::create([
            'subject' => 'Test campaign',
            'preheader' => 'A preview line.',
            'content' => '<p>Hello.</p>',
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $this->made[] = $c->id;

        return $c;
    }

    /** THE PREVIEW IS THE EMAIL. Both go through `renderHtml`, so this pins the shell too. */
    public function test_the_preview_renders_the_real_email_shell(): void
    {
        $html = $this->campaign()->renderHtml();

        $this->assertStringContainsString('Test campaign', $html);
        $this->assertStringContainsString('<p>Hello.</p>', $html, 'the body is missing');
        $this->assertStringContainsString('A preview line.', $html, 'the preheader is missing');
        $this->assertStringContainsString('Unsubscribe', $html, 'no unsubscribe link — this must never ship');
    }

    /**
     * TWO SENDS CANNOT BOTH START. `markSending` claims the row with a conditional update, so the
     * second caller gets nothing rather than a second copy of the email going out.
     */
    public function test_a_campaign_cannot_be_sent_twice(): void
    {
        Bus::fake();
        Subscriber::subscribe('one@campaign-test.invalid', 'One', 'test');

        $campaign = $this->campaign();

        $first = CampaignDispatcher::dispatch($campaign);
        $second = CampaignDispatcher::dispatch($campaign->fresh());

        /*
         * `>= 1`, NOT `=== 1`. These tests run against the development database without
         * `RefreshDatabase` — see RUNBOOK.md — so the list already has real subscribers on it and
         * the exact count is none of this test's business. The invariant being pinned is the
         * SECOND number: whatever the first send picked up, the second must pick up nothing.
         */
        $this->assertGreaterThanOrEqual(1, $first, 'the first send queued nobody at all');
        $this->assertSame(0, $second, 'the second send claimed the campaign again — it must not');
    }

    /** One row per person, and the unique index makes a resumed send safe. */
    public function test_dispatch_writes_one_delivery_row_per_subscriber(): void
    {
        Bus::fake();
        Subscriber::subscribe('a@campaign-test.invalid', 'A', 'test');
        Subscriber::subscribe('b@campaign-test.invalid', 'B', 'test');

        $campaign = $this->campaign();
        CampaignDispatcher::dispatch($campaign);

        $this->assertSame(
            2,
            $campaign->recipients()->whereIn('email', ['a@campaign-test.invalid', 'b@campaign-test.invalid'])->count(),
        );
    }

    /**
     * AN UNSUBSCRIBED ADDRESS IS NEVER WRITTEN TO, and the check happens at send time rather than
     * only when the list was assembled — somebody who leaves mid-send must not get the email
     * already sitting in the queue behind them.
     */
    public function test_an_unsubscribed_address_is_not_sent_to(): void
    {
        Mail::fake();

        $subscriber = Subscriber::subscribe('gone@campaign-test.invalid', 'Gone', 'test');
        $campaign = $this->campaign();

        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'status' => CampaignRecipient::STATUS_QUEUED,
        ]);

        $subscriber->unsubscribe();

        (new \App\Jobs\SendCampaignEmail($recipient))->handle();

        Mail::assertNothingSent();
        $this->assertSame(CampaignRecipient::STATUS_FAILED, $recipient->fresh()->status);
    }

    /** The happy path: the mail goes, the row says sent, the subscriber is stamped. */
    public function test_a_deliverable_subscriber_is_sent_to_and_recorded(): void
    {
        Mail::fake();

        $subscriber = Subscriber::subscribe('ok@campaign-test.invalid', 'Ok', 'test');
        $campaign = $this->campaign();

        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'status' => CampaignRecipient::STATUS_QUEUED,
        ]);

        (new \App\Jobs\SendCampaignEmail($recipient))->handle();

        Mail::assertSent(CampaignMail::class);
        $this->assertSame(CampaignRecipient::STATUS_SENT, $recipient->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->last_sent_at);
    }
}
