<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE AD CLICK BEHIND A LEAD.
 *
 * UTMs only exist because somebody hand-tagged a link. Google Ads AUTO-TAGGING — on by default, and
 * what Google recommends — appends `gclid` and no UTMs at all. So without these columns every click
 * we pay for arrives with no utm_source and lands in the panel as direct traffic: the spend looks
 * like it produced nothing, and organic looks better than it is.
 *
 * They are also the only key an ad platform will accept for an OFFLINE CONVERSION. A parent who
 * clicks an ad, books a call and enrols three weeks later can be tied back to that click by
 * uploading the id and nothing else. It cannot be reconstructed afterwards, which is why it is
 * stored at the moment of submission even though nothing reads it yet.
 *
 * TWO FIELDS AND A PLATFORM, NOT FIVE NULLABLE COLUMNS. Only one id can be present on a click, so
 * one column per platform would be four nulls on every row. The platform matters because the id
 * goes to a different API depending on who issued it — Google for gclid/gbraid/wbraid, Meta for
 * fbclid, Microsoft for msclkid.
 *
 * NOT PERSONAL DATA. These identify the CLICK, not the person: opaque to everybody except the
 * platform that minted them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            /* Last touch — the click this submission actually arrived on. */
            $table->string('click_id', 200)->nullable()->after('referrer');
            $table->string('click_platform', 20)->nullable()->after('click_id');

            /* First touch — kept for a conversion that only happens weeks later. */
            $table->string('first_click_id', 200)->nullable()->after('click_platform');

            /* Reports group spend by platform; the ids themselves are looked up, not grouped. */
            $table->index('click_platform');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['click_platform']);
            $table->dropColumn(['click_id', 'click_platform', 'first_click_id']);
        });
    }
};
