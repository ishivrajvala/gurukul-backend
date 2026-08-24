<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WHERE EACH LEAD CAME FROM.
 *
 * Without this the panel can say a family got in touch and nothing about how they found us — so
 * "which creator, campaign or search actually produces families" is unanswerable, and every
 * decision about where to spend is a guess dressed up as a judgement.
 *
 * COLUMNS, NOT THE EXISTING `payload` BLOB. Attribution is queried, grouped and counted — that is
 * what a report is — and grouping by a JSON key is both slow and untypeable. `payload` is for the
 * handful of extra fields one particular form happened to collect; this is for the six fields
 * EVERY form carries.
 *
 * FIRST TOUCH AND LAST TOUCH, BOTH. They answer different questions and the industry conflates them
 * constantly. First touch is what introduced the family to Avdhara — usually a creator or a search.
 * Last touch is what they clicked on the day they finally filled the form in — usually a retarget
 * or a direct visit. Crediting only one makes either discovery or closing look free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            /* Last touch: the visit this submission actually happened on. */
            $table->string('utm_source', 80)->nullable()->after('payload');
            $table->string('utm_medium', 80)->nullable()->after('utm_source');
            $table->string('utm_campaign', 120)->nullable()->after('utm_medium');

            /*
             * `utm_content` is where a creator's individual post lives — the difference between
             * "Instagram worked" and "that one reel worked", which is the only version worth
             * knowing when deciding what to commission next.
             */
            $table->string('utm_content', 120)->nullable()->after('utm_campaign');
            $table->string('utm_term', 120)->nullable()->after('utm_content');

            /* First touch: how they first arrived, kept even after ten later visits. */
            $table->string('first_source', 80)->nullable()->after('utm_term');
            $table->string('first_campaign', 120)->nullable()->after('first_source');

            /*
             * The page the form was on, and the page they arrived on. Different things: somebody can
             * land on an article and submit from the contact page, and only the pair tells you which
             * content did the persuading.
             */
            $table->string('landing_path', 200)->nullable()->after('first_campaign');
            $table->string('submitted_path', 200)->nullable()->after('landing_path');

            /* Where they came from when it was not a UTM link at all — a search, or another site. */
            $table->string('referrer', 200)->nullable()->after('submitted_path');

            /* Reports group by these three constantly. */
            $table->index('utm_source');
            $table->index('utm_campaign');
            $table->index('first_source');
        });

        /* The email list wants the same, and had only a single free-text `source`. */
        Schema::table('subscribers', function (Blueprint $table): void {
            $table->string('utm_source', 80)->nullable()->after('source');
            $table->string('utm_campaign', 120)->nullable()->after('utm_source');
            $table->string('landing_path', 200)->nullable()->after('utm_campaign');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table): void {
            $table->dropColumn(['utm_source', 'utm_campaign', 'landing_path']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['utm_campaign']);
            $table->dropIndex(['first_source']);
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                'first_source', 'first_campaign',
                'landing_path', 'submitted_path', 'referrer',
            ]);
        });
    }
};
