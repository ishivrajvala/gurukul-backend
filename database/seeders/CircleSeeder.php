<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\Circle;
use App\Models\Gathering;
use App\Models\Petal;
use App\Models\CircleTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The six Circles Avdhara holds, and the gatherings inside them.
 *
 * SIX, NOT DOZENS. A taxonomy a parent has to learn before participating is a taxonomy that stops
 * them participating — which is also why exactly one circle is flagged featured: there is always a
 * correct answer to "where do I start".
 *
 * Gathering dates are set RELATIVE TO NOW rather than fixed, so the upcoming/past split stays
 * meaningful however long after this seeder runs somebody looks at the admin. A fixed calendar of
 * past dates is worse than no seed data: it makes a running programme look abandoned.
 */
class CircleSeeder extends Seeder
{
    public function run(): void
    {
        $topics = CircleTopic::pluck('id', 'slug');
        $stages = AgeStage::pluck('id', 'key');
        $petals = Petal::pluck('id', 'slug');

        $circles = [
            [
                'slug' => 'raising-calm-capable-children',
                'name' => 'Raising Calm, Capable Children',
                'purpose' => 'The universal circle, and the best place to begin.',
                'description' => 'A welcoming starting place for conversations about attention, learning, emotions, relationships and everyday family life.',
                'is_featured' => true,
                /* No stages at all: this one is for EVERY stage, which is what makes it universal. */
                'stages' => [],
                'topics' => ['parenting', 'behaviour', 'confidence', 'learning'],
                'petals' => ['attention-self-mastery', 'emotional-intelligence-relationships', 'character-leadership'],
            ],
            [
                'slug' => 'learning-without-pressure',
                'name' => 'Learning Without Pressure',
                'purpose' => 'Reading, writing, maths, readiness and school concerns.',
                'description' => 'For the worry that sits under homework and report cards. What learning actually asks for at each age, and what it does not.',
                'is_featured' => false,
                'stages' => ['explorers', 'builders', 'thinkers'],
                'topics' => ['learning'],
                'petals' => ['learning-literacy', 'thinking-innovation', 'communication-expression'],
            ],
            [
                'slug' => 'big-feelings-growing-minds',
                'name' => 'Big Feelings & Growing Minds',
                'purpose' => 'Emotions, regulation, tantrums and confidence.',
                'description' => 'What is happening underneath the meltdown, the stalling and the silence, and what genuinely helps once you can see it.',
                'is_featured' => false,
                'stages' => ['seekers', 'explorers', 'builders'],
                'topics' => ['behaviour', 'confidence', 'health'],
                'petals' => ['emotional-intelligence-relationships', 'health-vitality', 'attention-self-mastery'],
            ],
            [
                'slug' => 'screens-attention-modern-childhood',
                'name' => 'Screens, Attention & Modern Childhood',
                'purpose' => 'Screen habits, attention, technology and AI.',
                'description' => 'Less about rules and more about the environment. What screens displace, what attention is built from, and where the useful lines sit.',
                'is_featured' => false,
                'stages' => ['builders', 'thinkers', 'leaders'],
                'topics' => ['technology-ai', 'safety'],
                'petals' => ['attention-self-mastery', 'creativity-nature-future-readiness', 'thinking-innovation'],
            ],
            [
                'slug' => 'friendship-behaviour-relationships',
                'name' => 'Friendship, Behaviour & Relationships',
                'purpose' => 'Listening, boundaries, conflict and friendships.',
                'description' => 'How children learn to belong, fall out, repair and hold their own ground, and when a parent should step in.',
                'is_featured' => false,
                'stages' => ['builders', 'thinkers', 'leaders'],
                'topics' => ['friends', 'behaviour'],
                'petals' => ['emotional-intelligence-relationships', 'communication-expression', 'character-leadership'],
            ],
            [
                'slug' => 'rooted-families',
                'name' => 'Rooted Families',
                'purpose' => 'Bharatiya wisdom, values, traditions and family culture.',
                'description' => 'Language, festivals, respect and belonging. Passing on what matters in a way a child chooses to keep rather than merely obeys.',
                'is_featured' => false,
                'stages' => [],
                'topics' => ['values-culture', 'money-life-skills'],
                'petals' => ['bharatiya-wisdom-identity', 'character-leadership'],
            ],
        ];

        foreach ($circles as $i => $data) {
            $circle = Circle::updateOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'purpose' => $data['purpose'],
                'description' => $data['description'],
                'managed_by_avdhara' => true,
                'is_featured' => $data['is_featured'],
                'is_published' => true,
                'position' => $i,
            ]);

            $circle->ageStages()->sync(collect($data['stages'])->map(fn ($k) => $stages[$k])->all());
            $circle->circleTopics()->sync(collect($data['topics'])->map(fn ($k) => $topics[$k])->all());
            $circle->petals()->sync(collect($data['petals'])->map(fn ($k) => $petals[$k])->all());
        }

        $ids = Circle::pluck('id', 'slug');

        /* [slug, circle, title, description, topic, days from now, time, format, city, capacity, taken, stages] */
        $gatherings = [
            ['when-reading-isnt-coming-yet', 'learning-without-pressure', "When Reading Isn't Coming Yet", 'A guided parent conversation about readiness, language and what actually supports reading.', 'learning', 7, '19:30', 'online', null, 15, 6, ['explorers', 'builders']],
            ['screens-without-daily-battles', 'screens-attention-modern-childhood', 'Screens Without Daily Battles', 'A practical conversation about designing the environment rather than fighting the screen.', 'technology-ai', 14, '19:00', 'online', null, 15, 11, ['builders', 'thinkers']],
            ['children-who-can-handle-big-feelings', 'big-feelings-growing-minds', 'Raising Children Who Can Handle Big Feelings', 'What co-regulation asks of the adult in the room, and how a calmer evening is actually built.', 'behaviour', 21, '10:30', 'in-person', 'Bengaluru', 12, 4, ['seekers', 'explorers']],
            ['when-friendships-get-complicated', 'friendship-behaviour-relationships', 'When Friendships Get Complicated', 'Falling out, being left out, and the difference between stepping in and stepping back.', 'friends', 28, '11:00', 'in-person', 'Pune', 12, 3, ['builders', 'thinkers']],
            ['keeping-our-languages-at-home', 'rooted-families', 'Keeping Our Languages at Home', 'What actually keeps a language alive in a house where the children answer in English.', 'values-culture', 35, '19:30', 'online', null, 15, 8, ['seekers', 'explorers', 'builders']],
            ['talking-to-teenagers-who-are-not-talking', 'friendship-behaviour-relationships', 'Talking to Teenagers Who Are Not Talking', 'Staying in the room when the door has closed, and what reopens it.', 'parenting', 42, '20:00', 'online', null, 15, 2, ['leaders', 'visionaries']],
            ['homework-without-the-evening-war', 'learning-without-pressure', 'Homework Without the Evening War', 'Where the line sits, how to talk to a school about it, and what to stop doing.', 'learning', 49, '19:00', 'in-person', 'Mumbai', 12, 5, ['builders', 'thinkers']],

            /* Past, kept rather than deleted: a parent who hears about a circle after it has run
               should see that it happened and comes round again. A page showing only the next three
               implies a programme that started last week. */
            ['the-first-hour-after-school', 'big-feelings-growing-minds', 'The First Hour After School', 'Why the hardest hour of the day is the one nobody plans for.', 'behaviour', -14, '19:30', 'online', null, 15, 15, ['explorers', 'builders']],
            ['confidence-that-is-not-praise', 'raising-calm-capable-children', 'Confidence That Is Not Praise', 'Where real confidence comes from, and why "you are so clever" works against it.', 'confidence', -21, '10:30', 'in-person', 'Bengaluru', 12, 12, ['builders', 'thinkers']],
            ['festivals-and-what-we-pass-on', 'rooted-families', 'Festivals, and What We Pass On', 'Keeping the meaning rather than only the procedure.', 'values-culture', -28, '19:00', 'online', null, 15, 13, []],
        ];

        foreach ($gatherings as [$slug, $circleSlug, $title, $description, $topic, $days, $time, $format, $city, $capacity, $taken, $stageKeys]) {
            [$h, $m] = explode(':', $time);

            $gathering = Gathering::updateOrCreate(['slug' => $slug], [
                'circle_id' => $ids[$circleSlug],
                'title' => $title,
                'description' => $description,
                'circle_topic_id' => $topics[$topic],
                'starts_at' => Carbon::now()->addDays($days)->setTime((int) $h, (int) $m),
                'format' => $format,
                'city' => $city,
                'capacity' => $capacity,
                'taken' => $taken,
                'is_published' => true,
            ]);

            $gathering->ageStages()->sync(collect($stageKeys)->map(fn ($k) => $stages[$k])->all());
        }
    }
}
