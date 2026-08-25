<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

/**
 * Load written article copy into the Journal.
 *
 *   php artisan journal:import-bodies path/to/bodies.json [--dry-run] [--overwrite]
 *
 * The file is `{ "<slug>": "<html>", ... }`.
 *
 * WHY A COMMAND AND NOT A SEEDER. `JournalSeeder` owns the INDEX — titles, filing, ordering — and
 * is explicitly forbidden from touching `content`, because re-running it must never wipe copy
 * written in the panel. Bodies move in the opposite direction and on a different schedule: they are
 * written a batch at a time over weeks. Two jobs, two entry points, and neither can destroy the
 * other's work by accident.
 *
 * IT REFUSES TO OVERWRITE BY DEFAULT. An article with copy already in it has almost certainly been
 * edited in the panel since it was imported — the panel is where article copy is owned — and
 * silently replacing that with whatever is in a file is how a week of editing disappears. Passing
 * `--overwrite` is a deliberate act; the summary always says how many were skipped for this reason.
 *
 * A SLUG WITH NO ARTICLE IS REPORTED, NOT IGNORED. It means the file and the archive disagree, and
 * that is worth knowing at import time rather than discovering as a piece that never appeared.
 */
class ImportArticleBodies extends Command
{
    protected $signature = 'journal:import-bodies
        {file : A JSON file of slug => HTML}
        {--dry-run : Report what would happen and write nothing}
        {--overwrite : Replace copy on articles that already have some}';

    protected $description = 'Import written article bodies into the Parent Journal';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("No such file: {$path}");

            return self::FAILURE;
        }

        try {
            /** @var array<string, string> $bodies */
            $bodies = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error('That file is not valid JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! is_array($bodies) || $bodies === []) {
            $this->error('Nothing to import — expected an object of slug => HTML.');

            return self::FAILURE;
        }

        $written = 0;
        $skipped = 0;
        $missing = [];
        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        foreach ($bodies as $slug => $html) {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                $missing[] = $slug;

                continue;
            }

            if (filled($article->content) && ! $overwrite) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                /*
                 * `content` only. Never the title, the filing or the dates — those belong to the
                 * index and to `JournalSeeder`, and an import that quietly re-titled an article
                 * would undo an editor's work in a field this file has no opinion about.
                 */
                $article->forceFill(['content' => $html])->save();
            }

            $written++;
        }

        $this->newLine();
        $this->line(($dryRun ? 'Would write' : 'Wrote').': '.$written);

        if ($skipped > 0) {
            $this->warn('Left alone (already written, no --overwrite): '.$skipped);
        }

        if ($missing !== []) {
            $this->warn('No article for '.count($missing).' slug(s):');
            foreach ($missing as $slug) {
                $this->line('  '.$slug);
            }
        }

        $total = Article::count();
        $done = Article::whereNotNull('content')->where('content', '!=', '')->count();
        $this->newLine();
        $this->info("Journal: {$done} of {$total} articles written.");

        return self::SUCCESS;
    }
}
