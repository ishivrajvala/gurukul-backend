<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Landing sections gain a COMPONENT and a VARIANT.
 *
 * The old `type` answered one question — what is this section — and the answer had a layout baked
 * into it: `points` meant both "a set of short titled paragraphs" AND "drawn as a three-column
 * grid". Six types was six layouts, and any new arrangement meant a seventh type.
 *
 * Splitting them gives an editor twenty-nine things a section can BE and sixteen ways it can LOOK,
 * without a component per pairing. See `models/landingComponents.ts` on the frontend, which is the
 * registry both sides read from.
 *
 * `type` IS KEPT, NOT DROPPED, and that is deliberate. It costs one nullable column and it is the
 * only record of what an editor originally chose — useful if a mapping below turns out to have
 * guessed wrong, and the difference between fixing that with an UPDATE and reconstructing it from a
 * dump. The frontend also still reads it as a fallback for any row this migration never saw, such
 * as one restored from an older backup.
 *
 * THE MAPPING IS CONSERVATIVE. `points` becomes Content laid out as Content + Points rather than
 * the narrower Promise component, because that is what it actually was: a heading and a grid of
 * short paragraphs, used for whatever an editor needed. Guessing Promise would relabel pages nobody
 * asked to have relabelled.
 */
return new class extends Migration
{
    /** old type => [component, variant] */
    private const MAPPING = [
        'hero' => ['hero', 'image-right'],
        'rich_text' => ['content', 'content-only'],
        'points' => ['content', 'content-points'],
        'faq' => ['faq', 'content-only'],
        'cta_band' => ['cta', 'content-only'],
        'form' => ['cta', 'content-only'],
    ];

    public function up(): void
    {
        Schema::table('landing_sections', function (Blueprint $table): void {
            $table->string('component')->nullable()->after('landing_page_id');
            $table->string('variant')->nullable()->after('component');
            /* `type` becomes history rather than the thing the renderer switches on. */
            $table->string('type')->nullable()->change();
        });

        foreach (self::MAPPING as $type => [$component, $variant]) {
            DB::table('landing_sections')
                ->where('type', $type)
                ->update(['component' => $component, 'variant' => $variant]);
        }

        /*
         * A `form` section carried its choice of form in `data.form`, and the CTA component still
         * reads exactly that key — so nothing needs moving. Rows whose `type` was anything else are
         * left with a null component and are skipped by the renderer, which is the same thing the
         * old switch did with a type it did not know.
         */
        Schema::table('landing_sections', function (Blueprint $table): void {
            $table->index('component');
        });
    }

    public function down(): void
    {
        Schema::table('landing_sections', function (Blueprint $table): void {
            $table->dropIndex(['component']);
            $table->dropColumn(['component', 'variant']);
        });

        /* `type` was never emptied, so there is nothing to restore into it. Rows created after this
           migration ran have no `type` and will be skipped by the old renderer — which is correct:
           they are sections it has no way to draw. */
    }
};
