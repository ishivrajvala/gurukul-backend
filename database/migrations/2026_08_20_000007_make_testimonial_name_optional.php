<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A testimonial does not have a name, and the schema should not insist on one.
 *
 * The column was NOT NULL from when testimonials were a generic marketing block. The Stories page
 * attributes every one of them to the pathway FAMILY — "Explorer family" — and never to a person:
 * these are the shortest, least-contextualised things on the page, so they carry the least
 * identifying detail. A sentence with a stranger's name attached invites a reader to weigh the
 * person rather than hear the words.
 *
 * Making it nullable rather than seeding a placeholder name. A fake "Anonymous" in a NOT NULL
 * column is a constraint being satisfied rather than respected, and it would eventually get
 * rendered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
        });
    }
};
