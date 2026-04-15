<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            if (Schema::hasColumn('testimonials', 'role')) {
                $table->dropColumn('role');
            }

            if (! Schema::hasColumn('testimonials', 'tags')) {
                $table->string('tags')->nullable()->after('name');
            }
        });

        DB::statement('ALTER TABLE testimonials ALTER COLUMN status SET DEFAULT true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            if (Schema::hasColumn('testimonials', 'tags')) {
                $table->dropColumn('tags');
            }

            if (! Schema::hasColumn('testimonials', 'role')) {
                $table->string('role')->nullable()->after('name');
            }
        });
    }
};
