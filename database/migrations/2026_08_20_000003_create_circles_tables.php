<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent Circles: standing spaces, and the gatherings held inside them.
 *
 * A CIRCLE IS A PLACE, NOT AN EVENT — the distinction the frontend model turns on. A circle is a
 * prepared, moderated space with a name and a purpose, joinable whether or not anything is
 * scheduled; a GATHERING is one facilitated hour inside it, with a date. Two tables, because they
 * are two things a parent commits to differently.
 *
 * NO THREADS, and no field that could become one. There was briefly a `Conversation` type on the
 * frontend and it was removed: it implied a forum this product cannot moderate yet, and a page of
 * other parents' unanswered questions promises a reply nobody is on the hook for. Circles run in a
 * WhatsApp group; the site is the front door.
 *
 * NO ENGAGEMENT COLUMNS ANYWHERE. `community.ts` locks the rule as "NO comment / view / like /
 * enrollment / rating counts", so there is no member count, no popularity, no rank. `capacity` and
 * `taken` exist because a facilitator needs them, and the site renders three availability bands
 * from them rather than "8 of 12 places left".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            /** One line: what this circle is for. */
            $table->string('purpose');
            /** The longer welcome, on the featured card and the circle's own dialog. */
            $table->text('description');
            /**
             * Modelled rather than assumed: every circle today is held by Avdhara, and
             * parent-hosted circles are the obvious next kind. The badge has to be able to tell
             * them apart before that happens, not after.
             */
            $table->boolean('managed_by_avdhara')->default(true);
            /** Exactly one circle is the universal starting place. Enforced in the app, not here. */
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });

        /* No `age_stages` rows at all means the circle is for EVERY stage — that is what makes the
           universal circle universal, and the API renders it as "All Ages" rather than listing six. */
        /*
         * Pivot names follow Laravel's convention — the two table names in ALPHABETICAL order — so
         * `belongsToMany` finds them without an explicit third argument. `circle_age_stage` reads
         * better in English and cost a runtime error on the first seed: a relation that guesses
         * wrong does not fail loudly at boot, it fails when somebody first touches the data.
         */
        Schema::create('age_stage_circle', function (Blueprint $table): void {
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['circle_id', 'age_stage_id']);
        });

        Schema::create('circle_topic', function (Blueprint $table): void {
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['circle_id', 'topic_id']);
        });

        /**
         * Which of the nine capacities a circle feeds.
         *
         * NOT SHOWN AS A TAXONOMY on the site: six parent-facing entry points map onto the internal
         * architecture, and exposing the whole architecture is what makes a parent feel they have
         * homework to do before speaking. It is recorded so the mapping is written down.
         */
        Schema::create('circle_petal', function (Blueprint $table): void {
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('petal_id')->constrained()->cascadeOnDelete();
            $table->primary(['circle_id', 'petal_id']);
        });

        Schema::create('gatherings', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            /**
             * The topic is on the GATHERING, not inherited from its circle. "Screens, Attention &
             * Modern Childhood" runs for six-year-olds and for teenagers, and they are not the same
             * hour; a parent filtering by age is asking which room to walk into.
             */
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            /* One timestamp rather than a date and a time string: "is this upcoming" is a
               comparison, and two columns make it two comparisons that can disagree. */
            $table->dateTime('starts_at');
            $table->enum('format', ['online', 'in-person'])->default('online');
            /** Null for online gatherings. */
            $table->string('city')->nullable();
            $table->unsignedSmallInteger('capacity')->default(12);
            $table->unsignedSmallInteger('taken')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'starts_at']);
        });

        Schema::create('age_stage_gathering', function (Blueprint $table): void {
            $table->foreignId('gathering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['gathering_id', 'age_stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_stage_gathering');
        Schema::dropIfExists('gatherings');
        Schema::dropIfExists('circle_petal');
        Schema::dropIfExists('circle_topic');
        Schema::dropIfExists('age_stage_circle');
        Schema::dropIfExists('circles');
    }
};
