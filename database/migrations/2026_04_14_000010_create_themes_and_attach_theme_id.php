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
        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $this->addThemeRelationToTable('landing_pages');
        $this->addThemeRelationToTable('blogs');
        $this->addThemeRelationToTable('announcements');
        $this->addThemeRelationToTable('testimonials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropThemeRelationFromTable('landing_pages');
        $this->dropThemeRelationFromTable('blogs');
        $this->dropThemeRelationFromTable('announcements');
        $this->dropThemeRelationFromTable('testimonials');

        Schema::dropIfExists('themes');
    }

    private function addThemeRelationToTable(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'theme_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('theme_id')->nullable()->constrained('themes')->nullOnDelete();
        });
    }

    private function dropThemeRelationFromTable(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'theme_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropForeign(['theme_id']);
            $table->dropColumn('theme_id');
        });
    }
};
