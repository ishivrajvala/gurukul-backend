<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgeStage;
use App\Models\ArticleTopic;
use App\Models\CircleTopic;
use App\Models\Petal;
use App\Models\StoryTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * The site's vocabularies.
 *
 * THREE LISTS WHERE THERE WAS ONE. `topics` used to be a single shared vocabulary that articles,
 * circles, gatherings and stories all filed against. It was split because the Journal's taxonomy is
 * now chosen for what parents type into a search engine — fifteen categories including STEAM and
 * Gurukul Education — and none of those are things a Circle meets about on a Tuesday evening, or a
 * family tells a story about. See the split migration for the full reasoning.
 *
 * THE `topics` KEY IS GONE RATHER THAN ALIASED. An alias would have let a consumer keep reading the
 * old key and silently receive the wrong list — article topics where it wanted circle topics —
 * which is a bug that presents as a content mistake and gets debugged in the wrong repository. A
 * missing key fails loudly, once, at the only place that can fix it.
 *
 * SHAPED TO MATCH THE FRONTEND'S OWN MODELS, field for field, so `models/journalTopics.ts`,
 * `models/circleTopics.ts`, `models/storyTags.ts` and `models/ages.ts` can each be replaced by a
 * fetch without renaming anything downstream. `slug` and `key` are the identifiers the site already
 * matches on, which is why they are authored rather than generated.
 *
 * Order is `position` throughout, never alphabetical: every one of these lists is hand-ordered and
 * the order carries meaning.
 */
class TaxonomyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'articleTopics' => $this->vocabulary(ArticleTopic::ordered()->get()),
            'circleTopics' => $this->vocabulary(CircleTopic::ordered()->get()),
            'storyTags' => $this->vocabulary(StoryTag::ordered()->get()),
            'ageStages' => AgeStage::ordered()->get()->map(fn (AgeStage $s): array => [
                'key' => $s->key,
                'name' => $s->name,
                'range' => $s->range_label,
                'from' => $s->age_from,
                'to' => $s->age_to,
            ]),
            'petals' => Petal::ordered()->get()->map(fn (Petal $p): array => [
                'slug' => $p->slug,
                'name' => $p->name,
                'number' => $p->number,
            ]),
        ]);
    }

    /**
     * The three taxonomies are the same shape and always will be — they were one table until
     * recently. One mapper, so a field added to the contract cannot reach two of them and miss the
     * third.
     *
     * @param  Collection<int, Model>  $rows
     * @return Collection<int, array<string, string>>
     */
    private function vocabulary(Collection $rows): Collection
    {
        return $rows->map(fn (Model $t): array => [
            'slug' => $t->slug,
            'name' => $t->name,
            'fullName' => $t->full_name,
            'eyebrow' => $t->eyebrow,
            'description' => $t->description,
        ]);
    }
}
