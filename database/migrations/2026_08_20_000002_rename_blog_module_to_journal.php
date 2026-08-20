<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blog becomes the Parent Journal.
 *
 * The blog module WAS the Journal all along — `models/journal-content.ts` on the frontend documents
 * the admin as `awcodes/filament-tiptap-editor` on `Blog::content` — but every other file in both
 * projects calls these articles. Renaming costs one migration; leaving it means the backend says
 * "blog" and the site says "journal" forever, and every new developer translates between them.
 *
 * Categories fold into the SHARED `topics` table rather than staying a blog-only taxonomy. That is
 * the whole point of the taxonomy migration: one vocabulary across Journal, Circles and Stories.
 * The old `blog_categories` rows are not migrated across — they were placeholder data and the ten
 * real topics are seeded from the frontend's locked list.
 *
 * The added columns are the ones the frontend actually reads and the blog table never had:
 * `standfirst` (the site's word for excerpt), `reading_minutes`, an image ALT, and the age pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('blogs', 'articles');

        Schema::table('articles', function (Blueprint $table): void {
            /* The site calls this the standfirst. Kept alongside `excerpt` rather than renamed on
               top of it, so nothing that still reads `excerpt` breaks mid-deploy; the seeder fills
               both and the API reads `standfirst`. */
            $table->text('standfirst')->nullable()->after('slug');
            $table->unsignedSmallInteger('reading_minutes')->default(5)->after('content');
            /* Alt text is REQUIRED editorially and nullable in the schema: an article can be drafted
               before its photograph is chosen, but it cannot be published without one. That rule
               belongs in the Filament resource, not in a NOT NULL that blocks a draft. */
            $table->string('featured_image_alt')->nullable()->after('featured_image');
            $table->foreignId('topic_id')->nullable()->after('category_id')->constrained('topics')->nullOnDelete();
        });

        /* Which stages a piece applies to. The eyebrow shows the span, the filter matches any — so
           it is many-to-many, not a single column. */
        /*
         * Pivot names follow Laravel's convention — the two table names in ALPHABETICAL order — so
         * `belongsToMany` finds them without an explicit third argument. `circle_age_stage` reads
         * better in English and cost a runtime error on the first seed: a relation that guesses
         * wrong does not fail loudly at boot, it fails when somebody first touches the data.
         */
        Schema::create('age_stage_article', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'age_stage_id']);
        });

        /**
         * The questions parents ask most. ORDERED AND HAND-PICKED, never computed from traffic:
         * `models/journal.ts` is explicit that a read count is the social proof the
         * attention-as-sacred rule forbids, which is also why the rail is called "Most asked" and
         * not "Most read".
         */
        Schema::create('most_asked', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });

        /* Trending search terms, also hand-picked. Same rule. */
        Schema::create('trending_searches', function (Blueprint $table): void {
            $table->id();
            $table->string('term');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('most_asked')->count();
    }

    public function down(): void
    {
        Schema::dropIfExists('trending_searches');
        Schema::dropIfExists('most_asked');
        Schema::dropIfExists('age_stage_article');

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
            $table->dropColumn(['standfirst', 'reading_minutes', 'featured_image_alt']);
        });

        Schema::rename('articles', 'blogs');
    }
};
