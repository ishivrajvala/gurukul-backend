<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An article can exist before it is written, and the schema should allow it.
 *
 * COMMISSIONED BUT UNWRITTEN IS A REAL STATE, not a gap. The frontend models it explicitly: a piece
 * has a title, a topic, an age span and a read time long before it has copy, and its reading page
 * says so plainly while the route puts `noindex` on it — rather than padding it out with filler
 * that would read as an article and be indexed as one. Thirty-five of the thirty-six imported
 * articles are in exactly that state.
 *
 * NOT NULL forced the alternative: seed an empty string, which is a constraint satisfied rather
 * than respected, and which `published()` could not tell apart from a real article.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->longText('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->longText('content')->nullable(false)->change();
        });
    }
};
