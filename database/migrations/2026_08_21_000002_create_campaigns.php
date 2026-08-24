<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAMPAIGNS — writing an email, looking at it, and sending it to the list.
 *
 * The email list existed with no way to write to it except a scheduled command that assembles the
 * week's articles by itself. That covers the weekly note and nothing else: there was no way to send
 * anything one-off, no way to see what an email would look like before it went, and no way to
 * schedule one for Tuesday.
 *
 * TWO TABLES, AND THE SECOND IS THE IMPORTANT ONE. `campaigns` is the email. `campaign_recipients`
 * is one row per person it was sent to, and it is what makes the questions "did this arrive" and
 * "who bounced" answerable at all. A counter on the campaign can say 4 failed; only a row per
 * address can say WHICH four, and only that lets a bounce be traced back to the send that caused it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();

            $table->string('subject', 200);

            /*
             * The grey line after the subject in most mail clients. It is the second thing anybody
             * reads and the most commonly wasted space in an email — left empty, clients fill it
             * with the first words of the body, which is usually "View this in your browser".
             */
            $table->string('preheader', 200)->nullable();

            /* The body as HTML, straight out of the editor. */
            $table->longText('content');

            /*
             * draft · scheduled · sending · sent · failed
             *
             * `sending` exists so a campaign cannot be sent twice by two people pressing the button
             * at once — see `Campaign::markSending`, which claims the row conditionally.
             */
            $table->string('status', 20)->default('draft')->index();

            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('sent_at')->nullable();

            /*
             * COUNTS ARE A CACHE, not the truth — `campaign_recipients` is. They exist so a list of
             * thirty campaigns does not run thirty aggregate queries to draw its table.
             */
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        /*
         * ONE ROW PER PERSON PER SEND. This is the delivery record.
         */
        Schema::create('campaign_recipients', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            /*
             * `nullOnDelete`, and the EMAIL IS COPIED beside it rather than only referenced. A
             * subscriber may be deleted; the fact that this campaign was sent to that address must
             * survive it, or a bounce arriving next week has nothing to attach to.
             */
            $table->foreignId('subscriber_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email', 190);

            /* queued · sent · failed · bounced · complained */
            $table->string('status', 20)->default('queued')->index();

            /* What the server said, when it said no. The only useful thing after a failure. */
            $table->text('error')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('bounced_at')->nullable();

            $table->timestamps();

            /*
             * NOBODY GETS THE SAME CAMPAIGN TWICE. This is the constraint that makes a re-run safe:
             * a send that dies halfway can be started again and the database refuses the duplicates
             * rather than trusting the code to remember where it got to.
             */
            $table->unique(['campaign_id', 'subscriber_id']);

            /* Bounce webhooks arrive knowing an address and nothing else. */
            $table->index('email');
        });

        Schema::table('subscribers', function (Blueprint $table): void {
            /*
             * WHY they bounced and WHEN, on the subscriber rather than only on the send. The status
             * says an address is dead; these say since when and on what grounds, which is what
             * separates "the mailbox is full this week" from "this address does not exist".
             */
            $table->timestamp('bounced_at')->nullable()->after('unsubscribed_at');
            $table->string('bounce_reason', 255)->nullable()->after('bounced_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table): void {
            $table->dropColumn(['bounced_at', 'bounce_reason']);
        });

        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
    }
};
