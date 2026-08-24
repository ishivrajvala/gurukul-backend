<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ENQUIRIES BECOMES LEADS, AND SUBSCRIBERS MOVES OUT OF IT.
 *
 * Five forms shared `enquiries`, and four of them belong together: somebody joining the waitlist,
 * writing in, booking a call or asking for the parent guide is a person waiting on a reply. A
 * newsletter subscriber is not. Nobody handles a subscriber — there is nothing to do about one,
 * ever, and it sat in the same queue as four things that all needed doing, adding to the same
 * pending count and reading as work that never got done.
 *
 * They are also on opposite clocks. A lead is answered ONCE and closed; a subscriber is written to
 * every week for years and the only thing that matters about them is whether that send still
 * lands. One table cannot hold both without one of the two states being a lie.
 *
 * `status` STOPS BEING SHARED. It was `pending / handled / declined` for everything, which is the
 * only vocabulary four different processes could agree on and describes none of them: a booking
 * that has been scheduled is neither pending nor handled, and "handled" says nothing about whether
 * anyone turned up. Each kind now carries its own workflow — see `App\Models\LeadKind` — and this
 * migration maps the old three onto the new ones per kind rather than dropping them.
 */
return new class extends Migration
{
    /**
     * The old shared status mapped onto each kind's own vocabulary.
     *
     * `handled` is the interesting one: it meant "somebody dealt with this" and its real successor
     * differs per process. A handled waitlist row means the family was invited; a handled booking
     * means the call happened. Guessing wrong in either direction is better than throwing the
     * distinction away, and there is no third option that keeps the information.
     */
    private const STATUS_MAP = [
        'waitlist' => ['pending' => 'new', 'handled' => 'invited', 'declined' => 'declined'],
        'contact' => ['pending' => 'new', 'handled' => 'answered', 'declined' => 'closed'],
        'booking' => ['pending' => 'new', 'handled' => 'completed', 'declined' => 'cancelled'],
        'parent-guide' => ['pending' => 'new', 'handled' => 'sent', 'declined' => 'sent'],
    ];

    public function up(): void
    {
        Schema::rename('enquiries', 'leads');

        /*
         * THE OLD `status` WAS AN ENUM, and on Postgres that is a CHECK constraint listing the
         * three values it was created with — `pending`, `handled`, `declined`. It survives the
         * rename under its ORIGINAL name, still enforcing a vocabulary this migration exists to
         * replace, so every UPDATE below would fail on a value it has never heard of.
         *
         * It has to be dropped BEFORE the data moves rather than after. `IF EXISTS` because the
         * name is Postgres's own construction and a fresh database built from a later schema may
         * not have it at all.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leads DROP CONSTRAINT IF EXISTS enquiries_status_check');
        }

        /*
         * THE EMAIL LIST. Not a queue — a list, and every column here exists because a weekly send
         * needs it and a lead inbox never did.
         */
        Schema::create('subscribers', function (Blueprint $table): void {
            $table->id();

            /*
             * UNIQUE, which `enquiries` could not be: somebody may write in three times and each
             * message is its own row, but subscribing twice must not mean two copies of every
             * weekly email. Re-subscribing updates the row that is already here.
             */
            $table->string('email', 190)->unique();
            $table->string('name', 120)->nullable();

            /* subscribed · unsubscribed · bounced — see `App\Models\Subscriber`. */
            $table->string('status', 20)->default('subscribed')->index();

            /* Which page the box was on. Useful when a send performs unusually badly. */
            $table->string('source', 60)->nullable();

            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            /*
             * A SECRET IN THE UNSUBSCRIBE LINK, never the email address or the id.
             *
             * The link goes out in an email and gets forwarded, quoted and indexed. `?email=` in
             * it lets anybody unsubscribe anybody, and a sequential id lets somebody walk the list.
             * A random token is the only version of this link that cannot be used against the
             * people on the list.
             */
            $table->string('unsubscribe_token', 64)->unique();

            /* When the last weekly email went out, so a re-run cannot send the same one twice. */
            $table->timestamp('last_sent_at')->nullable();

            $table->timestamps();
        });

        /* Existing newsletter rows move across rather than being dropped. */
        $existing = DB::table('leads')->where('kind', 'newsletter')->get();

        foreach ($existing as $row) {
            DB::table('subscribers')->insertOrIgnore([
                'email' => $row->email,
                'name' => $row->name,
                'status' => 'subscribed',
                'source' => 'newsletter',
                'subscribed_at' => $row->created_at,
                'unsubscribe_token' => Str::random(64),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        DB::table('leads')->where('kind', 'newsletter')->delete();

        /*
         * `career` predates the careers tables and cannot occur any more — see
         * `2026_08_20_000013_drop_enquiry_attachment`. Any survivor has no kind left to be.
         */
        DB::table('leads')->where('kind', 'career')->delete();

        foreach (self::STATUS_MAP as $kind => $map) {
            foreach ($map as $old => $new) {
                DB::table('leads')->where('kind', $kind)->where('status', $old)->update(['status' => $new]);
            }
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->string('status', 20)->default('new')->change();
        });
    }

    public function down(): void
    {
        /*
         * The subscribers go back as `newsletter` leads. Everything a weekly send needs — the
         * token, the bounce state, the last send — has nowhere to go in the old shape and is lost;
         * that is what makes this migration one-way in practice even though it reverses cleanly.
         */
        foreach (DB::table('subscribers')->get() as $row) {
            DB::table('leads')->insert([
                'kind' => 'newsletter',
                'name' => $row->name,
                'email' => $row->email,
                'status' => $row->status === 'subscribed' ? 'pending' : 'handled',
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        foreach (self::STATUS_MAP as $kind => $map) {
            foreach ($map as $old => $new) {
                DB::table('leads')->where('kind', $kind)->where('status', $new)->update(['status' => $old]);
            }
        }

        Schema::dropIfExists('subscribers');
        Schema::rename('leads', 'enquiries');
    }
};
