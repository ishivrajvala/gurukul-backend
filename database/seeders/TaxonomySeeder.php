<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\Petal;
use App\Models\Topic;
use Illuminate\Database\Seeder;

/**
 * The three shared vocabularies, copied from the frontend's locked models.
 *
 * SOURCE OF TRUTH, FOR NOW, IS THE FRONTEND. `models/topics.ts`, `models/ages.ts` and
 * `models/pathwayLibrary.ts` hold these lists and the site renders from them today. Seeding the
 * same slugs, names and ORDER is what lets the API replace those files later without a single
 * rename — and what stops the two projects growing two vocabularies.
 *
 * `updateOrCreate` on the slug, so re-running is safe and an editor's description edits survive a
 * reseed of the ordering.
 */
class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        /* The ten topics, in locked nav order. Never sorted alphabetically. */
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
            Topic::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'full_name' => $fullName,
                'eyebrow' => $eyebrow,
                'description' => $description,
                'position' => $i,
            ]);
        }

        /*
         * The six stages, youngest first. The names are the pathway names the site already routes
         * on (/seekers … /visionaries); comps have called 8-11 "Connectors" and 14-16 "Pathfinders"
         * and those would rename live pathways, so the locked names win.
         *
         * `range_label` uses an EN DASH, matching the site exactly.
         */
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

        /* The Nine Petals, in their framework numbering. */
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
