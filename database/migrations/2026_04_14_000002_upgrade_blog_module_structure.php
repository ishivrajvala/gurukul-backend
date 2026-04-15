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
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->renameColumn('excerpt', 'short_description');
        });

        Schema::table('blogs', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->boolean('is_featured')->default(false);
        });

        Schema::create('blog_tag', function (Blueprint $table): void {
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['blog_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_tag');

        Schema::table('blogs', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'is_featured']);
            $table->renameColumn('short_description', 'excerpt');
        });

        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
