<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The existing testimonials table, aligned to what the Stories page actually renders.
 *
 * Kept rather than replaced: it already holds the right KIND of thing. What it lacked is the one
 * field the site shows beside every quote — the pathway family — and a hand-order.
 *
 * ATTRIBUTION IS THE FAMILY, never a full name and never a photograph. These are the shortest,
 * least-contextualised things on the page, so they carry the least identifying detail: a sentence
 * with a stranger's face attached invites a reader to weigh the person rather than hear the words.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            if (! Schema::hasColumn('testimonials', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('testimonials', 'family_label')) {
                /** e.g. "Explorer family". The stage, not the child. */
                $table->string('family_label')->nullable();
            }
            if (! Schema::hasColumn('testimonials', 'position')) {
                $table->unsignedSmallInteger('position')->default(0);
            }
            if (! Schema::hasColumn('testimonials', 'is_published')) {
                $table->boolean('is_published')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropColumn(['family_label', 'position', 'is_published']);
        });
    }
};
