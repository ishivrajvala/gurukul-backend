<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent Stories: narratives, the short reflections, and the community screenshots.
 *
 * THREE FORMATS, ONE CONTENT TYPE. A story is written or it is a video, and `media_kind` is the
 * only column that differs. A parent browsing should experience them as one thing called a Parent
 * Story, and the CMS should need one resource rather than three.
 *
 * EVERY VIDEO IS A YOUTUBE EMBED. A second arm for self-hosted files was tried on the frontend and
 * removed: a reader could not tell them apart by design, so the only difference was ours to
 * maintain — a second player path, a CSP to widen, and transcoding for every clip a family sends.
 * Families upload to us and Avdhara publishes on its channel, which is why `youtube_id` is the only
 * media pointer here and `story_submissions` below takes a file.
 *
 * STORY IS NOT TESTIMONIAL. A story is a narrative with a page of its own; a testimonial is a
 * sentence with nowhere further to go. Two tables, because collapsing them gives you either
 * testimonials padded out to look like articles or stories flattened into pull quotes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            /** The parent's own sentence. Rendered in quotation marks. */
            $table->string('title');
            $table->text('standfirst');
            /** Plain paragraphs, not HTML: a parent story is somebody talking, so there is no
                markup to render and therefore none to sanitise. */
            $table->json('body')->nullable();

            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            /**
             * The petal is the EYEBROW; the topic is the FILTER. Different questions on purpose:
             * the topic is what a parent would search for ("Confidence"), the petal is what the
             * story turns out to be about developmentally ("Attention & Self Mastery").
             */
            $table->foreignId('petal_id')->nullable()->constrained()->nullOnDelete();

            /** Attribution is to the parent, never to Avdhara. */
            $table->string('author_name');
            $table->string('author_relation');
            /** The family's city, when they were happy to name it. Null is a valid answer. */
            $table->string('place')->nullable();

            $table->enum('media_kind', ['written', 'video'])->default('written');
            $table->string('youtube_id')->nullable();
            $table->enum('media_aspect', ['4/5', '16/9'])->default('4/5');
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->unsignedSmallInteger('reading_minutes')->default(4);
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });

        /*
         * Pivot names follow Laravel's convention — the two table names in ALPHABETICAL order — so
         * `belongsToMany` finds them without an explicit third argument. `circle_age_stage` reads
         * better in English and cost a runtime error on the first seed: a relation that guesses
         * wrong does not fail loudly at boot, it fails when somebody first touches the data.
         */
        Schema::create('age_stage_story', function (Blueprint $table): void {
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['story_id', 'age_stage_id']);
        });

        /**
         * Screenshots of what parents said elsewhere.
         *
         * `has_permission` IS NOT DECORATION. Google, Instagram and YouTube comments are public;
         * WhatsApp messages and emails are private, and republishing one without the family
         * agreeing is a breach whatever it says. The column exists so consent is part of the record
         * rather than something remembered in a spreadsheet, and the API refuses to return a row
         * without it.
         *
         * NO RATING, NO TOTAL, and no column that could produce "4.9 from 2,847 parents". Avdhara's
         * trust model runs on real family voices rather than social-proof numbers, and an average
         * is the moment a wall of individual voices becomes a marketing statistic.
         */
        Schema::create('community_reviews', function (Blueprint $table): void {
            $table->id();
            $table->enum('source', ['google', 'instagram', 'youtube', 'whatsapp', 'email']);
            $table->string('image_path');
            $table->string('image_alt');
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();
            $table->boolean('has_permission')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['has_permission', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reviews');
        Schema::dropIfExists('age_stage_story');
        Schema::dropIfExists('stories');
    }
};
