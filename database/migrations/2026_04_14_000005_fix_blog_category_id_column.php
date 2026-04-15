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
        $hasBlogCategoryId = Schema::hasColumn('blogs', 'blog_category_id');
        $hasCategoryId = Schema::hasColumn('blogs', 'category_id');

        if ($hasBlogCategoryId && ! $hasCategoryId) {
            Schema::table('blogs', function (Blueprint $table): void {
                $table->renameColumn('blog_category_id', 'category_id');
            });
        }

        if (! Schema::hasColumn('blogs', 'category_id')) {
            Schema::table('blogs', function (Blueprint $table): void {
                $table->foreignId('category_id')->nullable();
            });
        }

        if ($this->foreignKeyExists('blogs_category_id_foreign')) {
            Schema::table('blogs', function (Blueprint $table): void {
                $table->dropForeign('blogs_category_id_foreign');
            });
        }

        if (! $this->foreignKeyExists('blogs_category_id_foreign')) {
            Schema::table('blogs', function (Blueprint $table): void {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('blog_categories')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('blogs', 'category_id')) {
            return;
        }

        if ($this->foreignKeyExists('blogs_category_id_foreign')) {
            Schema::table('blogs', function (Blueprint $table): void {
                $table->dropForeign('blogs_category_id_foreign');
            });
        }
    }

    private function foreignKeyExists(string $constraintName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::selectOne(
                'select 1 from pg_constraint where conname = ? limit 1',
                [$constraintName]
            );

            return $result !== null;
        }

        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'select 1 from information_schema.table_constraints where constraint_schema = ? and constraint_name = ? limit 1',
            [$database, $constraintName]
        );

        return $result !== null;
    }
};
