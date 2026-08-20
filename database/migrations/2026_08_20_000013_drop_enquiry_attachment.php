<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The careers form used to post to `/v1/enquiries` as `kind = career`, with the CV in
 * `attachment_path`. It now has its own table, endpoint and inbox, because an application carries a
 * role, a required CV and a portfolio — columns rather than a JSON blob — and reading it three
 * weeks late because it sat between two newsletter signups costs a candidate a job.
 *
 * That leaves this column, and the `attachment` rule on the enquiry endpoint, with nothing sending
 * to them. Dropping both is the point: an unauthenticated endpoint that accepts documents from
 * strangers is worth having only while something needs it. Keeping it "just in case" leaves two
 * ways to submit a CV, one of which nobody watches.
 *
 * The `career` kind goes with it. It is not removed from any existing row because there are none —
 * the enquiries table is empty, and the only rows it ever held were this session's probes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            if (Schema::hasColumn('enquiries', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('attachment_path')->nullable();
        });
    }
};
