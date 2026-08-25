<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the one shared `topics` vocabulary into three module-owned ones.
 *
 * WHY THIS REVERSES A DELIBERATE DECISION. `topics` was built shared on purpose: articles, circles,
 * gatherings and stories all filed against one list in one locked order, so a parent who had learned
 * to navigate the Journal already knew how to navigate Circles. That held while all four modules
 * wanted the same ten labels.
 *
 * The Journal no longer does. Its taxonomy is now driven by what parents actually type into a search
 * engine — fifteen categories including STEAM, Gurukul Education and Learning Science — and none of
 * those are things a Circle or a Story files under. Forcing one list to serve both would either
 * strand the Journal's SEO categories on surfaces that have no use for them, or hold the Journal
 * back to a vocabulary chosen for a different job.
 *
 * So: three tables, each owned by the module that reads it.
 *
 *   article_topics   → articles                    (the fifteen; seeded separately)
 *   circle_topics    → circles, gatherings         (inherits the original ten, which fit it)
 *   story_tags       → stories                     (re-derived from what the stories are about)
 *
 * THIS MOVES DATA. Every existing row is copied into its new home BEFORE anything is dropped, ids
 * preserved so the foreign keys carry across unchanged, and the Postgres identity sequences are
 * reset afterwards — without that last step the next insert into any of the three collides with a
 * copied id.
 */
return new class extends Migration
{
    /** The shape all three inherit from `topics`. One definition, so they cannot drift apart. */
    private function taxonomyTable(string $name): void
    {
        Schema::create($name, function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            /** Short form: the label under an icon, e.g. "Learning". */
            $table->string('name');
            /** Long form: the heading of a category page, e.g. "Reading, Writing & Maths". */
            $table->string('full_name');
            /** The uppercase form above a title. A third field for the reason `topics` had one. */
            $table->string('eyebrow');
            $table->text('description');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });
    }

    /**
     * Postgres does not advance a sequence for an explicitly supplied id, so a table seeded by
     * copying rows still believes its next id is 1. Left alone, the next insert throws a unique
     * violation on the primary key — which surfaces as a save failing in the panel, not as anything
     * that points back here.
     */
    private function resyncSequence(string $table): void
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))",
            [$table]
        );
    }

    public function up(): void
    {
        foreach (['article_topics', 'circle_topics', 'story_tags'] as $name) {
            $this->taxonomyTable($name);

            /* Copy the ten across with their ids intact. Each module then edits its own copy: the
               Journal's is replaced wholesale by the seeder, Circles keeps all ten, Stories is
               re-derived. Copying first means no module is ever without a vocabulary mid-migration. */
            DB::statement("
                INSERT INTO {$name} (id, slug, name, full_name, eyebrow, description, position, created_at, updated_at)
                SELECT id, slug, name, full_name, eyebrow, description, position, created_at, updated_at FROM topics
            ");

            $this->resyncSequence($name);
        }

        /* ---- articles.topic_id → articles.article_topic_id ---- */
        Schema::table('articles', function (Blueprint $table): void {
            $table->foreignId('article_topic_id')->nullable()->after('slug')
                ->constrained('article_topics')->nullOnDelete();
        });
        DB::statement('UPDATE articles SET article_topic_id = topic_id');
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
        });

        /* ---- stories.topic_id → stories.story_tag_id ---- */
        Schema::table('stories', function (Blueprint $table): void {
            $table->foreignId('story_tag_id')->nullable()->after('slug')
                ->constrained('story_tags')->nullOnDelete();
        });
        DB::statement('UPDATE stories SET story_tag_id = topic_id');
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
        });

        /* ---- gatherings.topic_id → gatherings.circle_topic_id ----
           The topic sits on the gathering rather than being inherited from its circle, and that is
           deliberate — see GatheringResource. It follows Circles into `circle_topics`. */
        Schema::table('gatherings', function (Blueprint $table): void {
            $table->foreignId('circle_topic_id')->nullable()->after('circle_id')
                ->constrained('circle_topics')->nullOnDelete();
        });
        DB::statement('UPDATE gatherings SET circle_topic_id = topic_id');
        Schema::table('gatherings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
        });

        /* ---- circle_topic pivot → circle_circle_topic ---- */
        Schema::create('circle_circle_topic', function (Blueprint $table): void {
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('circle_topic_id')->constrained('circle_topics')->cascadeOnDelete();
            $table->primary(['circle_id', 'circle_topic_id']);
        });
        DB::statement('
            INSERT INTO circle_circle_topic (circle_id, circle_topic_id)
            SELECT circle_id, topic_id FROM circle_topic
        ');
        Schema::dropIfExists('circle_topic');

        Schema::dropIfExists('topics');
    }

    public function down(): void
    {
        /* Rebuild `topics` from the circle copy: it is the one table this migration leaves
           untouched in content, so it still holds the original ten. */
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('full_name');
            $table->string('eyebrow');
            $table->text('description');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });
        DB::statement('
            INSERT INTO topics (id, slug, name, full_name, eyebrow, description, position, created_at, updated_at)
            SELECT id, slug, name, full_name, eyebrow, description, position, created_at, updated_at FROM circle_topics
        ');
        $this->resyncSequence('topics');

        Schema::create('circle_topic', function (Blueprint $table): void {
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['circle_id', 'topic_id']);
        });
        DB::statement('
            INSERT INTO circle_topic (circle_id, topic_id)
            SELECT circle_id, circle_topic_id FROM circle_circle_topic
        ');
        Schema::dropIfExists('circle_circle_topic');

        foreach ([
            ['articles', 'article_topic_id'],
            ['stories', 'story_tag_id'],
            ['gatherings', 'circle_topic_id'],
        ] as [$table, $column]) {
            Schema::table($table, function (Blueprint $t): void {
                $t->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            });
            DB::statement("UPDATE {$table} SET topic_id = {$column}");
            Schema::table($table, function (Blueprint $t) use ($column): void {
                $t->dropConstrainedForeignId($column);
            });
        }

        Schema::dropIfExists('article_topics');
        Schema::dropIfExists('circle_topics');
        Schema::dropIfExists('story_tags');
    }
};
