<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last of the blog taxonomy, now that `topics` has replaced it.
 *
 * `articles.category_id` pointed at `blog_categories` and was NOT NULL, so an article could not be
 * filed against the SHARED topics without also being filed against a blog-only category that no
 * longer means anything. One vocabulary across Journal, Circles and Stories was the entire point of
 * the taxonomy module; two would have drifted within a month.
 *
 * `blog_tags` goes with it. The site has no tag concept at all — topics and age stages are the two
 * axes it filters on, and a third taxonomy nobody renders is a table that quietly fills up.
 *
 * Last of the removals, and after the replacement was seeded and verified. Dropping this first
 * would have taken the Journal's own filing with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            /* Named explicitly: the FK was created before this module and does not follow the
               convention `dropConstrainedForeignId` assumes. */
            if (Schema::hasColumn('articles', 'category_id')) {
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('articles', 'theme_id')) {
                $table->dropColumn('theme_id');
            }
        });

        /* `testimonials` also carried a theme, from when it was a generic marketing block.
           Postgres refuses to drop a table any foreign key still points at, so every referrer has
           to lose its column before `themes` can go. */
        if (Schema::hasColumn('testimonials', 'theme_id')) {
            Schema::table('testimonials', function (Blueprint $table): void {
                $table->dropColumn('theme_id');
            });
        }

        Schema::dropIfExists('blog_tag');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');

        /* The page builder and the theme system, whose models and resources are already gone. */
        Schema::dropIfExists('landing_page_sections');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('themes');
    }

    public function down(): void
    {
        /*
         * Deliberately not reversible. These tables held an earlier product's shape: a blog with
         * its own tags, a page builder and a themeable frontend. Recreating empty versions of them
         * would restore the schema without restoring anything that made it meaningful, and would
         * invite somebody to start using them again.
         */
    }
};
