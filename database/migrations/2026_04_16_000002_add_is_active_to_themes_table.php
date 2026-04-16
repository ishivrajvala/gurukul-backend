<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('themes') || Schema::hasColumn('themes', 'is_active')) {
            return;
        }

        Schema::table('themes', function (Blueprint $table): void {
            $table->boolean('is_active')->default(false)->after('end_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('themes') || ! Schema::hasColumn('themes', 'is_active')) {
            return;
        }

        Schema::table('themes', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
