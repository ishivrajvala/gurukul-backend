<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Order matters: taxonomy first, because Circles and Stories look their topics, stages and petals
 * up by slug. Everything is `updateOrCreate`, so re-running is safe.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TaxonomySeeder::class,
            JournalSeeder::class,
            CircleSeeder::class,
            StorySeeder::class,
        ]);
    }
}
