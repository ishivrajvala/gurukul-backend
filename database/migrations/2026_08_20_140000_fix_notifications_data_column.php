<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `notifications.data` becomes JSON.
 *
 * `php artisan notifications:table` scaffolds it as `text`, which is fine on MySQL — it casts on
 * demand — and broken on Postgres, which has no `->>` operator for text. Filament's notification
 * centre queries `data->>'format'` to tell its own notifications from anything else that uses the
 * same table, so with the bell switched on EVERY page in the panel threw:
 *
 *     SQLSTATE[42883]: operator does not exist: text ->> unknown
 *
 * The scaffolded migration is corrected too, so a fresh database never has the wrong type; this one
 * exists for the databases that already ran it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        /* `USING` is required: Postgres will not infer a text-to-json cast on its own. */
        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }
};
