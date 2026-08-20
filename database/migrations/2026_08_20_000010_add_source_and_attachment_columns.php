<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two columns the last of the site's forms need.
 *
 * `circle_questions.source` — the JOURNAL HAS AN ASK BOX TOO, and it is the same kind of thing: a
 * question a parent brought, read by a moderator, with no reply promised. It deliberately collects
 * no email, because asking for one would promise a personal answer nobody is going to send — which
 * is why it cannot go to `enquiries`, where an email is required. One table, one moderation queue,
 * and a column saying which surface it came from.
 *
 * `enquiries.attachment_path` — the careers form takes a CV. Storing the path in the `payload` blob
 * would work and would put a file reference somewhere nothing can index, validate or clean up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circle_questions', function (Blueprint $table): void {
            $table->string('source')->default('circle')->after('circle_id');
            $table->index('source');
        });

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('attachment_path')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('attachment_path');
        });

        Schema::table('circle_questions', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
