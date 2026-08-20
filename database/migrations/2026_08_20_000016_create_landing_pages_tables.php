<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing pages: a slug, SEO, and an ordered list of sections.
 *
 * A FIXED SET OF SECTION TYPES, NOT A FREE-FORM BUILDER. Each type maps onto a component that
 * already exists in the site's `views/shared/`, and the site's architecture is explicit that a
 * component must never be redefined locally — `SectionHead` was independently defined nine times
 * and the copies drifted. A builder that lets an editor assemble arbitrary blocks would produce
 * pages the design system has never seen, which is how a locked six-colour, four-type-role system
 * quietly stops being either.
 *
 * So `type` is an enum in practice, `data` is that type's fields, and adding a seventh type is a
 * deliberate change in two places rather than a row somebody inserts.
 *
 * `data` IS JSON, and that is the right trade here specifically. Six section types with six
 * different field sets would otherwise be six tables and six joins to render one page; nothing
 * queries inside a section, the whole page is read at once by slug, and the admin form already
 * knows which fields belong to which type. The rules that must be enforceable — is it published,
 * what order does it come in, which page does it belong to — are columns.
 *
 * SEO reuses the polymorphic `seo_meta`, the same table articles use. That was the point of it
 * being polymorphic, and a landing page is the surface that needs it most: it exists to be found.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table): void {
            $table->id();
            /*
             * The URL. Reserved words are refused in the admin rather than by a constraint here:
             * the list of routes the site already owns lives in the application, not the database,
             * and a landing page at `/contact` would be shadowed by the real page forever with
             * nothing saying why.
             */
            $table->string('slug')->unique();
            /* Internal, for the admin list. The visible headline is the hero section's own. */
            $table->string('title');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'slug']);
        });

        Schema::create('landing_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            /* One of the six types the frontend can render. See LandingSection::TYPES. */
            $table->string('type', 40);
            $table->json('data')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['landing_page_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_sections');
        Schema::dropIfExists('landing_pages');
    }
};
