<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE CAMPAIGN OBJECT'S MISSING FIELD.
 *
 * `announcements` already carries almost everything a campaign needs — `starts_at`, `ends_at`,
 * `audience`, `landing_page_id`, `priority`, and its own CTA. The one thing it could not do was
 * connect what a visitor SAW to what the reports COUNT.
 *
 * Without this, a seasonal strip that ran for six weeks and the leads it produced are two facts
 * with nothing joining them: the leads carry whatever UTM was on the link a visitor happened to
 * arrive by, and the strip carries nothing at all. Naming the campaign here — and appending it to
 * the strip's own CTA link — is what makes "how many families did the summer strip actually bring"
 * a query instead of a guess.
 *
 * DELIBERATELY NOT A FOREIGN KEY to the email `campaigns` table. These are different things that
 * share a word: one is an email that gets sent, this is a banner that gets shown. Joining them
 * would tie a strip's lifetime to an email's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            /*
             * The `utm_campaign` value to append to this strip's CTA. Free text, because campaign
             * names are invented by whoever runs the campaign and a fixed list would be out of date
             * the first time somebody launched something.
             */
            $table->string('utm_campaign', 120)->nullable()->after('cta_url');

            /*
             * `utm_source` too, so the strip is distinguishable from an ad pointing at the same
             * landing page in the same campaign. Defaults to `site` at read time when left empty —
             * a strip on our own site IS the source.
             */
            $table->string('utm_source', 80)->nullable()->after('utm_campaign');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn(['utm_campaign', 'utm_source']);
        });
    }
};
