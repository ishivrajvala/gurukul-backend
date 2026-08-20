<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shared taxonomy: topics, age stages and petals.
 *
 * THIS IS THE SPINE, and it is the piece the backend was missing entirely. The frontend files every
 * article, circle, gathering and story against the same three vocabularies, hand-authored in
 * `models/topics.ts`, `models/ages.ts` and `models/pathwayLibrary.ts`. Until they exist here, the
 * site cannot stop shipping its own seed data, and any admin that invents a fourth vocabulary
 * guarantees the two projects drift.
 *
 * `key`/`slug` are the frontend's own identifiers, not new ones. That is what makes the API a
 * drop-in for the hand-written models rather than a translation layer.
 *
 * `position` everywhere, because every one of these lists is HAND-ORDERED and the order carries
 * meaning: the ten topics are in a locked nav order, the six age stages run youngest to oldest, and
 * the nine petals are numbered in the framework. Nothing here is ever sorted alphabetically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            /** Short form: the label under an icon, e.g. "Learning". */
            $table->string('name');
            /** Long form: the heading of a topic page, e.g. "Learning & Academics". */
            $table->string('full_name');
            /**
             * The uppercase form above a title. A THIRD field on purpose: the design uses the long
             * name for Learning ("LEARNING & ACADEMICS") and short ones elsewhere ("BEHAVIOUR"), so
             * it cannot be derived from either of the other two.
             */
            $table->string('eyebrow');
            $table->text('description');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('age_stages', function (Blueprint $table): void {
            $table->id();
            /** The pathway key the site already routes on: seekers, explorers, builders... */
            $table->string('key')->unique();
            /** Pathway name, e.g. "Builders". */
            $table->string('name');
            /** The chip label, e.g. "6–8". En dash, locked. */
            $table->string('range_label');
            $table->unsignedTinyInteger('age_from');
            $table->unsignedTinyInteger('age_to');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('petals', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            /** The locked name, e.g. "Attention & Self Mastery". */
            $table->string('name');
            /** 1-9, the framework's own numbering. */
            $table->unsignedTinyInteger('number');
            $table->text('intro')->nullable();
            $table->timestamps();

            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petals');
        Schema::dropIfExists('age_stages');
        Schema::dropIfExists('topics');
    }
};
