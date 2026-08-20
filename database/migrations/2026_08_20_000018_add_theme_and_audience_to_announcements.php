<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What KIND of strip it is, and WHERE it shows.
 *
 * `theme` — an offer, a festival, a season, a special day, or plain news. It is not decoration: the
 * strip's icon comes from it, so a Diwali note and a careers announcement stop looking identical,
 * and the admin can be filtered to "every Diwali strip we have ever run" when next October comes
 * round. It does NOT choose a colour. Marigold with indigo text is a locked brand rule, and a
 * festival strip in a different palette is how a six-colour system becomes ten.
 *
 * `audience` — the strip is served by an API, and the website is not the only thing that will read
 * it. A mobile app and the curriculum surfaces are coming, and "the summer Circle opens in June" is
 * a message for parents on the website that an app already inside a paid programme should not be
 * shown. Defaulting to `all` keeps every strip that exists today behaving exactly as it does.
 *
 * Both are plain strings rather than database enums: adding a festival should be a line in a PHP
 * array, not a migration, and Postgres enum changes are a lock on a live table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('theme', 24)->default('news')->after('cta_label');
            $table->string('audience', 16)->default('all')->after('theme');
            $table->index(['audience', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropIndex(['audience', 'is_published']);
            $table->dropColumn(['theme', 'audience']);
        });
    }
};
