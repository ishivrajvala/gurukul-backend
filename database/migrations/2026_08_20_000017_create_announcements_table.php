<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The marigold strip above the nav.
 *
 * IT WAS A DEFAULT ARGUMENT IN A REACT COMPONENT. The message, the link text and the destination
 * were all hardcoded, so a seasonal note or an offer meant a developer and a deploy — for the one
 * piece of copy on the site whose whole value is being current.
 *
 * SCHEDULED, NOT SWITCHED. `starts_at` and `ends_at` are why this is a table rather than a single
 * settings row: a summer strip should appear and disappear on its own, because the failure mode of
 * a manual switch is a page still advertising a season that ended a month ago. Both are optional —
 * a strip with neither is simply always on.
 *
 * THE CTA IS A RESOLVED DESTINATION, not a typed URL. `cta_type` chooses between a page the site
 * already has, a landing page from the CMS, and an external address; the API turns whichever it is
 * into one href. A free-text path is a broken link waiting for a typo, and it cannot follow a
 * landing page whose slug changes.
 *
 * ONE SHOWS AT A TIME. `priority` breaks a tie between two strips whose windows overlap — which is
 * a normal thing to have, not a mistake, because a seasonal strip usually overlaps the standing one
 * it temporarily replaces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            /* Internal, for the admin list: two seasonal strips are otherwise hard to tell apart. */
            $table->string('label');
            $table->string('message');
            $table->string('cta_label')->nullable();

            $table->string('cta_type', 16)->default('route');   // route | landing | url
            $table->string('cta_route')->nullable();
            $table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cta_url')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
