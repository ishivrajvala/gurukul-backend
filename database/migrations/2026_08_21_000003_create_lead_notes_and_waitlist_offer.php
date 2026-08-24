<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WHAT HAPPENED TO THIS PARENT, and where a waitlist family goes next.
 *
 * ── THE NOTES ─────────────────────────────────────────────────────────────────────────────
 *
 * A status says where a lead has got to. It cannot say what was actually said. "Called" and
 * "Completed" are the same status whether the parent asked three questions about screen time or
 * said the timing is wrong and to try again in March — and the second is the only version worth
 * knowing before somebody rings them back.
 *
 * That knowledge currently lives in whoever made the call. This is where it goes instead.
 *
 * ONE ROW PER NOTE, APPEND ONLY. Not a `notes` text column on the lead: a single field gets
 * overwritten, loses who wrote what, and turns into an undated wall nobody reads. A trail is
 * ordered, attributed and additive, and it is the thing you scan before picking up the phone.
 *
 * ── THE WAITLIST OFFER ────────────────────────────────────────────────────────────────────
 *
 * A waitlist family who has been invited has to do something — register — and may be given an
 * offer to do it with. Both were invisible: `invited` was the last thing the process could say, so
 * a family who had registered and one who had been sent an invitation a month ago looked identical.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_notes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();

            /*
             * `nullOnDelete`, because a note has to outlive the account of whoever wrote it. A team
             * member leaving must not silently delete what they recorded about a family.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            /*
             * WHAT KIND OF CONTACT IT WAS — called, emailed, messaged, met, or just a note. Written
             * down rather than inferred from the text, so "we have tried three times" is a query
             * rather than a reading exercise.
             */
            $table->string('contact', 20)->default('note');

            $table->text('body');

            /*
             * The status the lead moved to, if this note came with a move. It makes the trail
             * self-explaining: you can see that the call happened AND that it is what marked the
             * booking completed, without holding two lists side by side.
             */
            $table->string('status_after', 20)->nullable();

            $table->timestamps();

            /* Always read newest-first for one lead. */
            $table->index(['lead_id', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            /*
             * THE OFFER IS A CODE AND A DATE, not a relationship to an offers table.
             *
             * There is no offer catalogue yet and inventing one to hold four seasonal codes would
             * be a table, a resource and a screen for something that is currently written on a
             * whiteboard. A code plus when it was applied answers the question actually being
             * asked — "did this family get something, and when" — and turns into a foreign key
             * later without losing a row.
             */
            $table->string('offer_code', 40)->nullable()->after('status');
            $table->timestamp('offer_applied_at')->nullable()->after('offer_code');

            /* When a waitlist family actually registered, which `invited` could not express. */
            $table->timestamp('registered_at')->nullable()->after('offer_applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn(['offer_code', 'offer_applied_at', 'registered_at']);
        });

        Schema::dropIfExists('lead_notes');
    }
};
