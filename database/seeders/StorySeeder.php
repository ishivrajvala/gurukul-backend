<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AgeStage;
use App\Models\CommunityReview;
use App\Models\Petal;
use App\Models\Story;
use App\Models\Testimonial;
use App\Models\StoryTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Parent Stories, the short testimonials, and the community screenshots.
 *
 * SEED CONTENT, WRITTEN TO THE POSITION THE SITE TAKES. It promises no developmental timeline and
 * makes no claim it cannot carry, but it is here so the admin and the API can be checked against
 * real prose rather than lorem — not because it is signed off.
 *
 * The video stories carry the placeholder YouTube id the frontend already ships, so a card renders
 * a real poster and a real player rather than a grey box while the page is being built.
 */
class StorySeeder extends Seeder
{
    /** A real id, and the same one the frontend uses, so posters resolve while this is seed data. */
    private const PLACEHOLDER_VIDEO = 'DxRoR1YeGAc';

    public function run(): void
    {
        $tags = StoryTag::pluck('id', 'slug');
        $petals = Petal::pluck('id', 'slug');
        $stages = AgeStage::pluck('id', 'key');

        $stories = [
            [
                'slug' => 'i-stopped-asking-whether-she-was-ahead',
                'title' => 'I stopped asking whether she was ahead, and started noticing how she was growing.',
                'standfirst' => 'A mother shares how letting go of comparison helped her see her daughter\'s unique journey and strengths.',
                'body' => [
                    'Every conversation with another parent had a scoreboard running underneath it. Who was reading, who was writing their name, who was still on picture books.',
                    'I stopped asking whether she was ahead and started noticing what she was actually doing, which took a surprising amount of effort because the first question is so much faster to answer.',
                    'What I saw once I looked was a child who was learning constantly and almost none of it in the places I had been checking. Our evenings changed from practice into stories and questions, and so did she.',
                ],
                'tag' => 'learning-without-pressure', 'petal' => 'learning-literacy',
                'author' => ['Priya', 'Parent of a 5-year-old'], 'place' => 'Bengaluru',
                'media' => 'video', 'duration' => 4, 'reading' => 6,
                'featured' => true, 'stages' => ['explorers'],
            ],
            [
                'slug' => 'the-biggest-change-was-in-how-i-responded',
                'title' => 'The biggest change was actually in how I responded.',
                'standfirst' => 'I went looking for something that would settle him. What I found was a habit of my own I had never looked at.',
                'body' => null,
                'tag' => 'parent-changed-first', 'petal' => 'emotional-intelligence-relationships',
                'author' => ['Divya', 'Parent of a 3-year-old'], 'place' => 'Hyderabad',
                'media' => 'video', 'duration' => 3, 'reading' => 3,
                'featured' => true, 'stages' => ['seekers'],
            ],
            [
                'slug' => 'bedtime-stopped-being-a-battle',
                'title' => 'Bedtime stopped becoming a battle every evening.',
                'standfirst' => 'What changed was not a new rule. It was the twenty minutes before the rule was ever needed.',
                'body' => null,
                'tag' => 'ending-a-daily-battle', 'petal' => 'health-vitality',
                'author' => ['Sneha', 'Parent of a 5-year-old'], 'place' => 'Bengaluru',
                'media' => 'video', 'duration' => 2, 'reading' => 3,
                'featured' => true, 'stages' => ['explorers'],
            ],
            [
                'slug' => 'he-used-to-say-i-cant-before-even-trying',
                'title' => "He used to say 'I can't' before even trying.",
                'standfirst' => 'A parent shares what changed when they stopped rushing in to help and started believing in his journey.',
                'body' => [
                    'It started with shoelaces and spread to everything. He would look at a thing, decide it was beyond him, and put it down before his hands had touched it. I kept telling him he was clever, which I now think was part of the problem.',
                    'What changed was not something I said. It was learning to sit on my hands. He would struggle, I would feel the pull to step in and finish it for him, and I would count instead.',
                    'He still says he cannot do things. The difference is that now it is the first thing he says rather than the last, and somewhere behind it he has started to expect that he will be wrong about that.',
                ],
                'tag' => 'finding-confidence', 'petal' => 'character-leadership',
                'author' => ['Kavita', 'Parent of a 7-year-old'], 'place' => 'Pune',
                'media' => 'written', 'duration' => null, 'reading' => 5,
                'featured' => false, 'stages' => ['builders'],
            ],
            [
                'slug' => 'i-wanted-to-solve-every-friendship-problem',
                'title' => 'I wanted to solve every friendship problem for her.',
                'standfirst' => 'What one parent learned about stepping back and building her daughter\'s resilience.',
                'body' => [
                    'She would come home and tell me who had been unkind, and by the time she had finished the sentence I had already drafted the email to her teacher. It took me most of a year to notice that she was not asking me to fix anything.',
                    'A friend suggested I try answering with a question instead of a plan. So I started asking what she thought she might do. The first few times she said she did not know, and we sat in that.',
                    'The friendships did not get easier. She got steadier inside them.',
                ],
                'tag' => 'stepping-back-socially', 'petal' => 'emotional-intelligence-relationships',
                'author' => ['Anjali', 'Parent of a 9-year-old'], 'place' => 'Mumbai',
                'media' => 'written', 'duration' => null, 'reading' => 7,
                'featured' => false, 'stages' => ['thinkers'],
            ],
            [
                'slug' => 'our-house-speaks-three-languages',
                'title' => 'Our house speaks three languages.',
                'standfirst' => 'For two years the children answered everything in English. What brought the others back was not a rule.',
                'body' => [
                    'For two years the children understood everything and answered everything in English. It felt like watching something slide out of reach one conversation at a time.',
                    'A rule would have made it a chore, and we had seen that go badly elsewhere. What worked was giving the other languages somewhere to live: cooking, a grandparent on video most weeks, songs in the car.',
                    'They still answer in English about half the time. The difference is that the other languages are now attached to people and to things they want, rather than to a rule they are failing at.',
                ],
                'tag' => 'language-and-belonging', 'petal' => 'bharatiya-wisdom-identity',
                'author' => ['Padma', 'Parent of two'], 'place' => 'Chennai',
                'media' => 'written', 'duration' => null, 'reading' => 6,
                'featured' => false, 'stages' => ['seekers', 'explorers', 'builders'],
            ],
            [
                'slug' => 'we-stopped-doing-homework-checks',
                'title' => 'We stopped doing homework checks.',
                'standfirst' => 'The first fortnight was worse than we feared. The month after that was better than we hoped.',
                'body' => [
                    'We had checked every piece of work every night for four years. He was eleven and had never once handed something in without one of us having read it first.',
                    'The first fortnight after we stopped was worse than we had feared. Two things went in half-done and one did not go in at all, and we had to sit on our hands through the consequences.',
                    'It turned out he had been outsourcing the checking to us, and once it was his, he did it. Not as well as we did. Well enough, and it is his.',
                ],
                'tag' => 'letting-go-of-control', 'petal' => 'thinking-innovation',
                'author' => ['Arun', 'Parent of an 11-year-old'], 'place' => 'Indore',
                'media' => 'written', 'duration' => null, 'reading' => 5,
                'featured' => false, 'stages' => ['thinkers', 'leaders'],
            ],
            [
                'slug' => 'my-sixteen-year-old-wants-a-different-path',
                'title' => 'My sixteen-year-old wants a different path.',
                'standfirst' => 'Everything we had planned assumed one route. Listening properly meant giving up a picture I was attached to.',
                'body' => [
                    'Everything we had planned assumed one route, and we had been assuming it out loud in front of her for about a decade.',
                    'When she told us, my first reaction was to explain why she was wrong. What eventually helped was asking her to tell me the whole of it and not saying anything until she had finished.',
                    'I am certain that it is her decision, and that the picture I was attached to was mine rather than a prediction about her. Those are not the same thing and I had them confused for years.',
                ],
                'tag' => 'choosing-a-different-path', 'petal' => 'creativity-nature-future-readiness',
                'author' => ['Bhavna', 'Parent of a 16-year-old'], 'place' => 'Mumbai',
                'media' => 'written', 'duration' => null, 'reading' => 8,
                'featured' => false, 'stages' => ['visionaries'],
            ],
        ];

        foreach ($stories as $i => $data) {
            /* RE-TAGGED, not remapped. These used to carry a subject topic from the shared ten,
               which produced labels that were true and useless — a story about a parent who stopped
               checking homework was filed under "Learning", beside articles on reading levels. The
               tag now names the turn the story takes. See TaxonomySeeder::storyTags(). */

            $story = Story::updateOrCreate(['slug' => $data['slug']], [
                'title' => $data['title'],
                'standfirst' => $data['standfirst'],
                'body' => $data['body'],
                'story_tag_id' => $tags[$data['tag']],
                'petal_id' => $petals[$data['petal']],
                'author_name' => $data['author'][0],
                'author_relation' => $data['author'][1],
                'place' => $data['place'],
                'media_kind' => $data['media'],
                'youtube_id' => $data['media'] === 'video' ? self::PLACEHOLDER_VIDEO : null,
                'media_aspect' => '4/5',
                'duration_minutes' => $data['duration'],
                'reading_minutes' => $data['reading'],
                'is_featured' => $data['featured'],
                'is_published' => true,
                'published_at' => Carbon::now()->subDays($i * 2),
            ]);

            $story->ageStages()->sync(collect($data['stages'])->map(fn ($k) => $stages[$k])->all());
        }

        /*
         * Testimonials. Attribution is the pathway FAMILY, never a full name and never a
         * photograph: these are the least-contextualised things on the page, so they carry the
         * least identifying detail. A sentence with a stranger's face attached invites a reader to
         * weigh the person rather than hear the words.
         */
        $testimonials = [
            ["For the first time I don't feel like I need to turn every moment into a lesson.", 'Explorer family'],
            ['It has changed the questions we ask our child more than the answers we expect from her.', 'Builder family'],
            ['What I appreciate most is that nothing feels rushed.', 'Seeker family'],
            ['I stopped comparing her to the child next door. That alone made our evenings quieter.', 'Explorer family'],
            ['My husband and I finally have the same words for what we are trying to do. We argue about it far less.', 'Thinker family'],
            ['He tells me things now. I did not realise how much I had stopped hearing.', 'Leader family'],
            ['Boredom is allowed in our house now. It turns out to be where most of the good ideas come from.', 'Builder family'],
            ['I ask what she wants from me before I start fixing it. Half the time the answer is nothing.', 'Thinker family'],
            ['The evening has a shape now. Nobody wrote it down and everybody knows it.', 'Explorer family'],
            ['I spend far less time worrying about whether he is behind, and far more time enjoying him.', 'Seeker family'],
            ['We still lose our tempers. We repair properly afterwards, and that turned out to be the part that mattered.', 'Builder family'],
            ['She explains things to me now, at length, whether I asked or not. I would not trade it.', 'Visionary family'],
        ];

        foreach ($testimonials as $i => [$quote, $family]) {
            Testimonial::updateOrCreate(
                ['slug' => 'testimonial-' . ($i + 1)],
                [
                    /* `type` USED TO BE HERE and is not any more. It predated this module as a
                       NOT NULL enum of text|youtube, and was dropped by
                       `2026_08_20_000011_drop_legacy_testimonial_columns` once the filmed ones
                       became Stories — which is the whole point of keeping the two apart. Writing
                       it broke `db:seed` outright for every seeder after this one. */
                    'content' => $quote,
                    'family_label' => $family,
                    'position' => $i,
                    'is_published' => true,
                ],
            );
        }

        /*
         * Community screenshots. Seeded WITHOUT permission on the private channels, deliberately:
         * WhatsApp and email are private messages, the API refuses to return a row without consent,
         * and seed data that pretends consent exists is how a real breach gets shipped.
         */
        $reviews = [
            ['google', 'A Google review from a parent about the daily rhythm at home', 600, 800, true],
            ['instagram', 'An Instagram comment from a parent about screen time', 600, 900, true],
            ['youtube', 'A YouTube comment from a parent about a video on attention', 600, 500, true],
            ['google', 'A Google review from a parent about their child asking more questions', 600, 620, true],
            ['whatsapp', 'A WhatsApp message from a parent, pending their written permission', 600, 750, false],
            ['email', 'An email from a parent, pending their written permission', 600, 880, false],
        ];

        foreach ($reviews as $i => [$source, $alt, $w, $h, $permission]) {
            CommunityReview::updateOrCreate(
                ['image_path' => "stories/reviews/{$source}-" . ($i + 1) . '.png'],
                [
                    'source' => $source,
                    'image_alt' => $alt,
                    'image_width' => $w,
                    'image_height' => $h,
                    'has_permission' => $permission,
                    'position' => $i,
                ],
            );
        }
    }
}
