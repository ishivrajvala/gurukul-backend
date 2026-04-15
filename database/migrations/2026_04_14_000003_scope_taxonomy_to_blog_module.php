<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('categories', 'blog_categories');
        Schema::rename('tags', 'blog_tags');

        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->unique('name', 'blog_categories_name_unique');
        });

        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->unique('name', 'blog_tags_name_unique');
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->renameColumn('category_id', 'blog_category_id');
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->foreign('blog_category_id')
                ->references('id')
                ->on('blog_categories')
                ->cascadeOnDelete();
        });

        Schema::table('blog_tag', function (Blueprint $table): void {
            $table->dropForeign(['tag_id']);
            $table->foreign('tag_id')
                ->references('id')
                ->on('blog_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_tag', function (Blueprint $table): void {
            $table->dropForeign(['tag_id']);
            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
                ->cascadeOnDelete();
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->dropForeign(['blog_category_id']);
            $table->renameColumn('blog_category_id', 'category_id');
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });

        Schema::table('blog_tags', function (Blueprint $table): void {
            $table->dropUnique('blog_tags_name_unique');
        });

        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->dropUnique('blog_categories_name_unique');
        });

        Schema::rename('blog_tags', 'tags');
        Schema::rename('blog_categories', 'categories');
    }
};
