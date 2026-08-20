<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\Article;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The Parent Journal, imported from the frontend.
 *
 * READ FROM `database/data/journal.json`, which is extracted straight out of `models/journal.ts`
 * rather than retyped. That file is the source of truth TODAY — it is what the live site renders —
 * so importing it is the only way to reach parity without somebody hand-entering thirty-six
 * articles and introducing thirty-six chances to differ.
 *
 * BODIES ARE NOT IMPORTED, and that is not an oversight. The frontend keeps its copy in a separate
 * `journal-content.ts` and only one article has any; the other thirty-five are commissioned but
 * unwritten.
 *
 * They still arrive PUBLISHED, because that is what the site actually does with them: all
 * thirty-six appear in the feed as cards with a title, a topic and an age span, and it is the
 * READING PAGE that says a piece is not written yet, while the route puts `noindex` on it. Status
 * governs whether a piece is listed at all; an empty body governs what its own page says. Importing
 * them as drafts would have emptied a Journal that is currently full.
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

        $topics = Topic::pluck('id', 'slug');
        $stages = AgeStage::pluck('id', 'key');

        foreach ($data['articles'] as $row) {
            $article = Article::updateOrCreate(['slug' => $row['slug']], [
                'title' => $row['title'],
                'standfirst' => $row['standfirst'],
                /* The legacy column is `short_description`; `standfirst` is the site's word for the
                   same thing. Both filled so nothing reading the old one breaks. */
                'short_description' => $row['standfirst'],
                'topic_id' => $topics[$row['category']] ?? null,
                'reading_minutes' => $row['readingMinutes'],
                'is_featured' => (bool) $row['isFeatured'],
                'published_at' => $row['publishedAt'],
                /* Listed, as on the live site. An empty body is handled by the reading page, not
                   by hiding the piece. See the class note. */
                'status' => 'published',
                'featured_image' => $row['image']['src'] ?? null,
                'featured_image_alt' => $row['image']['alt'] ?? null,
            ]);

            $article->ageStages()->sync(
                collect($row['ageStages'])->map(fn (string $k) => $stages[$k] ?? null)->filter()->all(),
            );
        }

        /* Hand-picked and hand-ordered, never computed from traffic — the same reason the rail is
           called "Most asked" rather than "Most read". */
        DB::table('most_asked')->delete();
        foreach ($data['mostAsked'] as $i => $row) {
            DB::table('most_asked')->insert([
                'question' => $row['question'],
                'article_id' => Article::where('slug', $row['slug'])->value('id'),
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
}
