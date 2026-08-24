<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WHERE AVDHARA IS, PUBLICLY.
 *
 * The four profile URLs were literals in the website's `models/seo.ts`, which meant a new channel —
 * or a changed handle — was a code change and a deploy. Marketing opening a TikTok account should
 * not need a developer.
 *
 * ONE ROW PER PLATFORM, SEEDED EMPTY. Every platform Avdhara might use exists as a row from the
 * start with a blank url, so adding TikTok is pasting a link into a field that is already there
 * rather than knowing to create something. A blank url is the OFF switch: the API omits those rows
 * entirely, so an unused platform is simply absent from the site rather than an icon linking
 * nowhere.
 *
 * `platform` IS THE KEY, not a display name. The website maps it to an icon, so it has to be a
 * stable slug — renaming "twitter" to "X" in the panel must not silently drop the icon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table): void {
            $table->id();

            /* Unique: two rows for the same platform would render the same icon twice. */
            $table->string('platform', 32)->unique();
            $table->string('label', 60);

            /*
             * NULLABLE IS THE POINT. Null or empty means "we are not on this platform yet", and the
             * API filters those out. It is not an error state.
             */
            $table->string('url', 300)->nullable();

            /* Display order, so the row can be arranged without renaming anything. */
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
