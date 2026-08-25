<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\ArticleTopic;
use App\Models\CircleTopic;
use App\Models\Petal;
use App\Models\StoryTag;
use Illuminate\Database\Seeder;

/**
 * The site's vocabularies.
 *
 * THREE LISTS WHERE THERE WAS ONE. `topics` was a single shared table that articles, circles,
 * gatherings and stories all filed against. It was split because the Journal's taxonomy is now
 * chosen for search and the other two modules have no use for it — see the split migration.
 *
 * Where each list comes from now differs, and that is the important part:
 *
 *   ARTICLE TOPICS come from `database/data/journal.json`, alongside the articles filed under them.
 *   One file, so a topic cannot be added without the articles that justify it, and the seeder
 *   cannot file an article under a topic that does not exist.
 *
 *   CIRCLE TOPICS are the original ten, written out here. They were designed for this job and all
 *   six circles already sit across them cleanly.
 *
 *   STORY TAGS are new, and are the one list here that is not a subject vocabulary at all.
 *
 * `updateOrCreate` on the slug throughout, so re-running is safe and an editor's description edits
 * survive a reseed of the ordering.
 */
class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->articleTopics();
        $this->circleTopics();
        $this->storyTags();
        $this->ageStages();
        $this->petals();
    }

    /**
     * The fifteen Journal topics, read from the same file as the articles.
     *
     * TOPICS NOT IN THE FILE ARE DELETED. This list is generated from the frontend model and is
     * meant to match it exactly; a topic left behind from a previous shape would appear in the
     * panel's filter dropdown and on the site's topic rail as a category with nothing in it. The
     * articles that referenced it are replaced in the same run, so nothing is orphaned by this.
     */
    private function articleTopics(): void
    {
        $path = database_path('data/journal.json');

        if (! is_file($path)) {
            $this->command?->warn('journal.json not found — article topics skipped.');

            return;
        }

        /** @var array{categories: array<int, array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $keep = [];

        foreach ($data['categories'] as $i => $row) {
            ArticleTopic::updateOrCreate(['slug' => $row['slug']], [
                'name' => $row['name'],
                'full_name' => $row['fullName'],
                'eyebrow' => $row['eyebrow'],
                'description' => $row['description'],
                'position' => $i,
            ]);
            $keep[] = $row['slug'];
        }

        ArticleTopic::whereNotIn('slug', $keep)->delete();
    }

    /** The original ten, in locked nav order. Never sorted alphabetically. */
    private function circleTopics(): void
    {
        $topics = [
            ['learning', 'Learning', 'Learning & Academics', 'Learning & Academics', 'Helping parents understand how reading, writing, mathematics, science and learning develop without turning childhood into a race.'],
            ['behaviour', 'Behaviour', 'Behaviour & Emotions', 'Behaviour', 'What is going on underneath the shouting, the stalling and the silence, and what actually helps once you can see it.'],
            ['friends', 'Friends', 'Friendships & Social Skills', 'Friendships', 'How children learn to belong, fall out, repair and stand on their own, and when a parent should step in.'],
            ['confidence', 'Confidence', 'Confidence & Self Belief', 'Confidence', 'Where real confidence comes from, why praise often works against it, and how to rebuild it once it has slipped.'],
            ['health', 'Health', 'Health & Wellbeing', 'Health', 'Sleep, food, movement and worry. The ordinary things that quietly decide how the rest of the week goes.'],
            ['safety', 'Safety', 'Safety & Body Awareness', 'Safety', 'How to give a child the words and the permission to keep themselves safe, without handing them fear instead.'],
            ['technology-ai', 'Technology & AI', 'Technology & AI', 'Technology & AI', 'Screens, phones and now AI. What each one is doing to attention and thinking, and where the useful lines sit.'],
            ['money-life-skills', 'Money & Life Skills', 'Money & Life Skills', 'Money & Life Skills', 'The practical competence a child leaves home with, built one ordinary responsibility at a time.'],
            ['values-culture', 'Values & Culture', 'Values & Culture', 'Values & Culture', 'Passing on language, respect and belonging in a way a child chooses to keep rather than merely obeys.'],
            ['parenting', 'Parenting', 'Parenting & Family Life', 'Parenting', 'The part nobody asks about. Guilt, comparison, exhaustion and the question of whether you are getting this right.'],
        ];

        foreach ($topics as $i => [$slug, $name, $fullName, $eyebrow, $description]) {
            CircleTopic::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'full_name' => $fullName,
                'eyebrow' => $eyebrow,
                'description' => $description,
                'position' => $i,
            ]);
        }
    }

    /**
     * What a family CHANGED, not what subject the story is about.
     *
     * This is the one taxonomy here that is not a subject vocabulary, and the difference is the
     * reason it exists. Under the old shared list, the story of a parent who stopped checking
     * homework every evening was filed as "Learning" — true, and useless, because it sat beside
     * articles about reading levels and told a reader nothing about why they would open it.
     *
     * A reader browsing stories is not choosing a subject to study. They are looking for a family
     * that was in the situation they are in now. So these are named for the turn the story takes,
     * and each of the eight current stories has one that fits exactly rather than approximately.
     */
    private function storyTags(): void
    {
        $tags = [
            ['parent-changed-first', 'The parent changed first', 'When the parent changed first', 'The parent changed first', 'Families where nothing improved until an adult altered what they were doing, rather than the child.'],
            ['letting-go-of-control', 'Letting go of control', 'Letting go of control', 'Letting go of control', 'Parents who stopped supervising something, and found out what their child did with the space.'],
            ['learning-without-pressure', 'Learning without pressure', 'Learning without pressure', 'Learning without pressure', 'What changed when the question stopped being whether a child was ahead.'],
            ['ending-a-daily-battle', 'Ending a daily battle', 'Ending a daily battle', 'Ending a daily battle', 'The recurring fight — bedtime, homework, the morning — and how one household stopped having it.'],
            ['finding-confidence', 'Finding confidence', 'Finding confidence', 'Finding confidence', 'Children who did not believe they could, and what actually shifted it.'],
            ['stepping-back-socially', 'Stepping back socially', 'Stepping back from friendships', 'Stepping back socially', 'Parents who wanted to solve a friendship problem, and learned what happens when they do not.'],
            ['language-and-belonging', 'Language & belonging', 'Language, culture and belonging', 'Language & belonging', 'Raising a child inside more than one language or culture, and what it gives them.'],
            ['choosing-a-different-path', 'Choosing a different path', 'Choosing a different path', 'Choosing a different path', 'Families who took a route nobody around them was taking, and how that conversation went.'],
        ];

        foreach ($tags as $i => [$slug, $name, $fullName, $eyebrow, $description]) {
            StoryTag::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'full_name' => $fullName,
                'eyebrow' => $eyebrow,
                'description' => $description,
                'position' => $i,
            ]);
        }

        /* PRUNE WHAT THE SPLIT LEFT BEHIND. The migration copied all ten of the old shared topics
           into this table so no story was ever tagless mid-migration. Stories have since been
           re-tagged onto the eight above, leaving ten empty subject labels that would show up as
           filter chips leading to nothing. */
        StoryTag::whereNotIn('slug', array_column($tags, 0))->delete();
    }

    /**
     * The six stages, youngest first. The names are the pathway names the site already routes on
     * (/seekers … /visionaries); comps have called 8-11 "Connectors" and 14-16 "Pathfinders" and
     * those would rename live pathways, so the locked names win.
     *
     * `range_label` uses an EN DASH, matching the site exactly.
     */
    private function ageStages(): void
    {
        $stages = [
            ['seekers', 'Seekers', '2–4', 2, 4],
            ['explorers', 'Explorers', '4–6', 4, 6],
            ['builders', 'Builders', '6–8', 6, 8],
            ['thinkers', 'Thinkers', '8–11', 8, 11],
            ['leaders', 'Leaders', '11–14', 11, 14],
            ['visionaries', 'Visionaries', '14–16', 14, 16],
        ];

        foreach ($stages as $i => [$key, $name, $range, $from, $to]) {
            AgeStage::updateOrCreate(['key' => $key], [
                'name' => $name,
                'range_label' => $range,
                'age_from' => $from,
                'age_to' => $to,
                'position' => $i,
            ]);
        }
    }

    /** The Nine Petals, in their framework numbering. */
    private function petals(): void
    {
        $petals = [
            ['attention-self-mastery', 'Attention & Self Mastery'],
            ['learning-literacy', 'Learning & Literacy'],
            ['communication-expression', 'Communication & Expression'],
            ['thinking-innovation', 'Thinking & Innovation'],
            ['emotional-intelligence-relationships', 'Emotional Intelligence & Relationships'],
            ['health-vitality', 'Health & Vitality'],
            ['character-leadership', 'Character & Leadership'],
            ['bharatiya-wisdom-identity', 'Bharatiya Wisdom & Identity'],
            ['creativity-nature-future-readiness', 'Creativity, Nature & Future Readiness'],
        ];

        foreach ($petals as $i => [$slug, $name]) {
            Petal::updateOrCreate(['slug' => $slug], ['name' => $name, 'number' => $i + 1]);
        }
    }
}
