<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things an editor could not control, and one that answers "which five and which one leads".
 *
 * `stories.home_position` — WHICH STORIES APPEAR ON THE HOMEPAGE, AND WHICH ONE LEADS.
 *
 * The homepage band took `is_featured` and sorted by date, so the five it showed were whichever
 * five were flagged, and the lead was whichever of those was newest. Nobody could choose. Worse,
 * `is_featured` is also what drives the Parent Stories carousel, so the two surfaces could not
 * differ: promoting a story on the hub promoted it on the homepage as a side effect.
 *
 * A separate nullable column keeps them independent. Null means "not on the homepage"; 1 is the
 * lead, 2 to 5 the row beneath. It is deliberately not a boolean plus a separate "featured" flag —
 * one ordered list cannot disagree with itself about which story is first.
 *
 * `seo_meta` — PER-ARTICLE SEO. No schema change was needed: the table was created with the right
 * shape and then never used by anything, so the titles and descriptions Google shows were derived
 * from the article's own headline and standfirst — which are written for a reader who has already
 * arrived, not for one deciding whether to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->unsignedSmallInteger('home_position')->nullable()->after('is_featured');
            $table->index('home_position');
        });

        /* The lookup index is already there — `morphs()` in the original migration created it. Left
           as a note rather than a second one: the table was simply never used. */
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropIndex(['home_position']);
            $table->dropColumn('home_position');
        });
    }
};
