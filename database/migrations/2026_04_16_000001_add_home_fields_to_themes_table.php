<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('themes')) {
            return;
        }

        Schema::table('themes', function (Blueprint $table): void {
            if (! Schema::hasColumn('themes', 'is_home_active')) {
                $table->boolean('is_home_active')->default(false)->after('end_date');
            }

            if (! Schema::hasColumn('themes', 'banner_text')) {
                $table->text('banner_text')->nullable()->after('is_home_active');
            }

            if (! Schema::hasColumn('themes', 'banner_cta_text')) {
                $table->string('banner_cta_text')->nullable()->after('banner_text');
            }

            if (! Schema::hasColumn('themes', 'banner_cta_link')) {
                $table->string('banner_cta_link')->nullable()->after('banner_cta_text');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('themes')) {
            return;
        }

        Schema::table('themes', function (Blueprint $table): void {
            if (Schema::hasColumn('themes', 'banner_cta_link')) {
                $table->dropColumn('banner_cta_link');
            }

            if (Schema::hasColumn('themes', 'banner_cta_text')) {
                $table->dropColumn('banner_cta_text');
            }

            if (Schema::hasColumn('themes', 'banner_text')) {
                $table->dropColumn('banner_text');
            }

            if (Schema::hasColumn('themes', 'is_home_active')) {
                $table->dropColumn('is_home_active');
            }
        });
    }
};
