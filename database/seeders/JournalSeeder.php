<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\Article;
use App\Models\ArticleTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The Parent Journal, imported from the frontend.
 *
 * READ FROM `database/data/journal.json`, which is generated from `models/journal.ts` and
 * `models/journalTopics.ts` by `scripts/extract-journal.mjs` in the frontend repo rather than
 * retyped. That file is the source of truth for the INDEX today — it is what the live site renders
 * when the API is unreachable — so importing it is the only way to reach parity without somebody
 * hand-entering a hundred and seventy-seven articles.
 *
 * BODIES ARE NOT IMPORTED, and that is not an oversight. The frontend has no copy of them: article
 * copy is written in the panel and lives in `articles.content`, which is the one direction of
 * ownership this project has settled. Re-running this seeder must therefore NEVER touch `content` —
 * `updateOrCreate` below lists every column it writes explicitly, and `content` is not among them,
 * so a reseed refreshes titles, filing and ordering while leaving written copy alone. This is the
 * single most damaging thing that could go wrong in this file.
 *
 * Articles arrive PUBLISHED, because that is what the site does with them: every row appears in the
 * feed as a card with a title, a topic and an age span, and it is the READING PAGE that says a
 * piece is not written yet, while the route puts `noindex` on it. Status governs whether a piece is
 * listed at all; an empty body governs what its own page says. Importing them as drafts would empty
 * a Journal that is meant to be full.
 *
 * ROWS NOT IN THE FILE ARE DELETED. The taxonomy was replaced wholesale — thirty-six articles filed
 * under ten topics became a hundred and seventy-seven under fifteen — and a leftover article would
 * point at a topic that no longer exists, appear in the feed under no category, and break the
 * sitemap. Anything with copy written against it is reported rather than silently removed.
 */
class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/journal.json');

        if (! is_file($path)) {
            $this->command?->warn('journal.json not found — run the frontend extractor first.');

            return;
        }

        /** @var array{articles: array<int, array<string, mixed>>, mostAsked: array<int, array<string, string>>, trending: array<int, string>} $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $topics = ArticleTopic::pluck('id', 'slug');
        $stages = AgeStage::pluck('id', 'key');

        $keep = [];

        foreach ($data['articles'] as $row) {
            if (! isset($topics[$row['topic']])) {
                $this->command?->warn("skipped {$row['slug']}: no topic {$row['topic']}");

                continue;
            }

            $article = Article::updateOrCreate(['slug' => $row['slug']], [
                'title' => $row['title'],
                'standfirst' => $row['standfirst'],
                /* The legacy column is `short_description`; `standfirst` is the site's word for the
                   same thing. Both filled so nothing reading the old one breaks. */
                'short_description' => $row['standfirst'],
                'article_topic_id' => $topics[$row['topic']],
                'reading_minutes' => $row['readingMinutes'],
                'is_featured' => (bool) $row['isFeatured'],
                'published_at' => $row['publishedAt'],
                'status' => 'published',
                'featured_image' => $row['image']['src'] ?? null,
                'featured_image_alt' => $row['image']['alt'] ?? null,
            ]);

            $article->ageStages()->sync(
                collect($row['ageStages'])->map(fn (string $k) => $stages[$k] ?? null)->filter()->all(),
            );

            $keep[] = $row['slug'];
        }

        $this->removeStaleArticles($keep);

        /* Hand-picked and hand-ordered, never computed from traffic — the same reason the rail is
           called "Most asked" rather than "Most read". Rebuilt after the articles exist, because
           each row is only useful if it can resolve to one. */
        DB::table('most_asked')->delete();
        foreach ($data['mostAsked'] as $i => $row) {
            $id = Article::where('slug', $row['slug'])->value('id');

            if ($id === null) {
                $this->command?->warn("most asked: no article for {$row['slug']}");

                continue;
            }

            DB::table('most_asked')->insert([
                'question' => $row['question'],
                'article_id' => $id,
                'position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('trending_searches')->delete();
        foreach ($data['trending'] as $i => $term) {
            DB::table('trending_searches')->insert([
                'term' => $term,
                'position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Drop articles the file no longer lists.
     *
     * WRITTEN COPY IS REPORTED, NOT DESTROYED QUIETLY. A stale row with a body is somebody's work,
     * and deleting it on a reseed with no trace is how a project loses an article and never finds
     * out. The delete still happens — a row filed under a topic that no longer exists cannot stay —
     * but the command prints exactly which slugs carried copy, so it can be recovered from a dump
     * if it mattered.
     *
     * @param  array<int, string>  $keep
     */
    private function removeStaleArticles(array $keep): void
    {
        $stale = Article::whereNotIn('slug', $keep)->get(['id', 'slug', 'content']);

        if ($stale->isEmpty()) {
            return;
        }

        $written = $stale->filter(fn (Article $a): bool => filled($a->content));

        if ($written->isNotEmpty()) {
            $this->command?->warn(
                'removing '.$written->count().' article(s) that HAD WRITTEN COPY: '
                .$written->pluck('slug')->implode(', ')
            );
        }

        Article::whereIn('id', $stale->pluck('id'))->delete();

        $this->command?->info('removed '.$stale->count().' article(s) no longer in journal.json');
    }
}
