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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            /*
             * JSON, NOT TEXT, and on Postgres that is the difference between working and a 500.
             *
             * `notifications:table` scaffolds `text`, which is fine on MySQL where `->>` will
             * happily cast. Filament's notification centre queries `data->>'format'` to tell its
             * own notifications from any others, and Postgres has no `->>` operator for text:
             * every page with the bell on it threw
             * `operator does not exist: text ->> unknown` — which is every page.
             */
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
