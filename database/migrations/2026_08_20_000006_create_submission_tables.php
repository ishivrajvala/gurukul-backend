<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every form on the site. All of them were dead ends before this.
 *
 * The frontend forms are fully built — validation, every state, the confirmation copy — and none of
 * them POST anywhere; `viewmodels/useForm.ts` flips `isSent` and discards the input. These tables
 * are the other end. Until they exist, a parent who says yes reaches nobody.
 *
 * NOTHING AUTO-PUBLISHES AND NOTHING AUTO-ADMITS. Every table here carries a `status` that starts
 * at `pending`, because each of these promises a person will read it: a story is reviewed before
 * publication, a question is moderated before it appears, and a WhatsApp invite is sent by hand
 * after somebody approves. Those promises are in the confirmation copy the parent has already read.
 *
 * A `handled_at` and `handled_by` on each, so "did anyone answer this family" is answerable.
 */
return new class extends Migration
{
    public function up(): void
    {
        /**
         * Joining a Circle, or reserving a place at one gathering. One table, because they ask a
         * parent for exactly the same four things and differ only in what they are saying yes to —
         * which is what `kind` records.
         *
         * THE WHATSAPP NUMBER IS THE POINT. Circles run in a WhatsApp group and a gathering is a
         * call announced there, so this is not a contact detail filed away: it is the address of
         * the room. Stored as given rather than normalised, because a human reads every one before
         * anybody is added and numbering plans differ by country.
         */
        Schema::create('circle_signups', function (Blueprint $table): void {
            $table->id();
            $table->enum('kind', ['join', 'reserve']);
            $table->foreignId('circle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gathering_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('email');
            $table->string('whatsapp');
            $table->foreignId('age_stage_id')->nullable()->constrained()->nullOnDelete();
            /** "Anything you would like to bring" — a question they are carrying, if any. */
            $table->text('note')->nullable();

            $table->enum('status', ['pending', 'approved', 'invited', 'declined'])->default('pending');
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        /**
         * Ask the Circle.
         *
         * ANONYMITY IS NOT A LESSER MODE. Circles receive questions about neurodivergence,
         * developmental worry, family difficulty, illness and grief, and a parent who has to attach
         * their name mostly does not ask. `is_anonymous` means anonymous TO OTHER PARENTS: the
         * moderator still sees the row, which is what keeps the space held rather than unattended.
         *
         * There is deliberately no required contact field. Asking for one would promise a personal
         * reply that nobody is going to send, and it would undo the point of asking anonymously.
         */
        Schema::create('circle_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('circle_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->boolean('is_anonymous')->default(false);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'published', 'answered', 'declined'])->default('pending');
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        /**
         * Share your story.
         *
         * ONE CONSENT, and the child clause is INSIDE it rather than in a second box. Avdhara's
         * framework requires explicit family consent for identifiable child stories and images;
         * three separate ticks is the pattern everybody clicks through without reading, which
         * protects a family less than one they actually read. `consent_text` stores the exact
         * wording agreed to, because a consent you cannot reproduce is not one you can rely on.
         *
         * A VIDEO IS UPLOADED, not linked. Asking for a YouTube URL asks a parent to publish their
         * child's video publicly before deciding whether to share it with us at all, which is
         * backwards for a moderated surface. Avdhara publishes to its channel afterwards.
         */
        Schema::create('story_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->foreignId('age_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->text('story')->nullable();
            $table->string('video_path')->nullable();
            $table->string('photo_path')->nullable();

            $table->boolean('has_consent')->default(false);
            /** The exact sentence the parent agreed to, as shown. */
            $table->text('consent_text')->nullable();

            $table->enum('status', ['pending', 'approved', 'published', 'declined'])->default('pending');
            /** Set once an approved submission becomes a real story. */
            $table->foreignId('story_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        /**
         * The remaining site forms, in one table.
         *
         * Waitlist, contact, newsletter, booking enquiries, the parent guide and career
         * applications differ by a couple of fields each and by nothing structural, so a `kind` and
         * a `payload` beats six near-identical tables and six near-identical Filament resources.
         * The three above are separate because each carries a rule the schema itself has to hold:
         * a WhatsApp number, an anonymity flag, a consent record.
         */
        Schema::create('enquiries', function (Blueprint $table): void {
            $table->id();
            $table->enum('kind', ['waitlist', 'contact', 'newsletter', 'booking', 'parent-guide', 'career']);
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('age_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            /** Everything else the specific form collected, as given. */
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'handled', 'declined'])->default('pending');
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('story_submissions');
        Schema::dropIfExists('circle_questions');
        Schema::dropIfExists('circle_signups');
    }
};
