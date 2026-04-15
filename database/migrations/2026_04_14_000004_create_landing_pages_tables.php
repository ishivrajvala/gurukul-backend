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
        Schema::create('landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_page_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')->constrained('landing_pages')->cascadeOnDelete();
            $table->string('type');
            $table->integer('order')->default(0);
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_sections');
        Schema::dropIfExists('landing_pages');
    }
};
