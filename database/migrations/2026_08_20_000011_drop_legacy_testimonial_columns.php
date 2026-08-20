<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last of the pre-Avdhara testimonial schema.
 *
 * Eight columns survived the rebuild because dropping a column is the one migration you cannot take
 * back, so they were left alone until it was clear nothing wanted them. Nothing does: the API
 * returns slug, quote and family label; the admin screen edits those plus position and published;
 * and every one of these is either empty in all twelve rows or a single constant.
 *
 *   title, name, tags, video_url, thumbnail   NULL in every row
 *   type                                      'text' in every row
 *   is_featured, status                       one value in every row
 *
 * `type` and `status` were NOT NULL, which is why `TestimonialResource` carried a hidden field
 * defaulting `type` to 'text' — a form field that existed solely to satisfy a column nobody reads.
 * That goes with them.
 *
 * A VIDEO TESTIMONIAL IS A STORY, which is the substantive reason `video_url` and `thumbnail` are
 * not worth keeping "just in case". A reflection with a face and a voice attached is a narrative
 * with a page of its own, and that is what `stories` is for. Keeping the columns here would invite
 * somebody to file one in the wrong table.
 *
 * `is_featured` has no meaning on this surface either: there is no "featured testimonial" anywhere
 * on Parent Stories, and there should not be — the band is a rail of equals, and promoting one
 * sentence over another is the ranking behaviour this site does not do.
 *
 * The down() recreates them nullable rather than as they were. It is a rollback of the structure,
 * not of data that no longer exists; restoring NOT NULL on a column with no values would fail.
 */
return new class extends Migration
{
    private const COLUMNS = ['type', 'title', 'name', 'tags', 'video_url', 'thumbnail', 'is_featured', 'status'];

    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('testimonials', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->text('tags')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_featured')->nullable();
            $table->boolean('status')->nullable();
        });
    }
};
