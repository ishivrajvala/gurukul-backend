<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The homepage's "You are not alone" band: three figures, and three parent concerns.
 *
 * TWO TABLES, because they are two different things to write. A figure is a number and a sentence
 * about it; a concern is a headline, three bullets, a link and a picture. One table with a `kind`
 * column would leave every row half empty and the admin form guessing which half to show.
 *
 * WHAT IS NOT HERE IS DESIGN. No colours, no borders, no tints. The first card is indigo, the
 * second marigold, the third green, and that is a locked palette decision that belongs in the view
 * — an editor choosing a hex is how a six-colour design system quietly becomes twelve. `position`
 * therefore picks the accent as well as the order. The icon IS editable, from a fixed set, because
 * a new concern needs an icon that matches what it is about.
 *
 * THE FIGURES ARE CLAIMS ABOUT THE WORLD, not about Avdhara — "1 in 3 children show signs of
 * attention difficulty by age 8" is research, not a testimonial, which is why this band is not
 * covered by the site's no-counts rule. It does mean they need to be true: `source` exists so the
 * person writing one has somewhere to record where it came from, and so the next person can check.
 * Nothing renders it yet, deliberately — it is a note to the editor, not a citation on the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_stats', function (Blueprint $table): void {
            $table->id();
            /* "73%", "2.4x", "1 in 3". A string, not a number: the shape of it is the point. */
            $table->string('figure', 24);
            $table->text('description');
            $table->string('icon', 32)->default('people');
            /* Where the figure came from. For the editor and the next editor, not for the page. */
            $table->string('source')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });

        Schema::create('home_concerns', function (Blueprint $table): void {
            $table->id();
            /* "One Story", "Another Story". The small uppercase line above the headline. */
            $table->string('eyebrow', 60);
            $table->string('title');
            /* Three short lines. A JSON array rather than a child table: they are prose with no
               identity and nothing joins to them. */
            $table->json('bullets')->nullable();
            $table->string('cta')->nullable();
            $table->string('cta_href')->default('/development-pathways');
            $table->string('icon', 32)->default('phone');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_concerns');
        Schema::dropIfExists('home_stats');
    }
};
