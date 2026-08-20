<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgeStage;
use App\Models\Petal;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;

/**
 * The shared vocabularies.
 *
 * SHAPED TO MATCH THE FRONTEND'S OWN MODELS, field for field, so `models/topics.ts` and
 * `models/ages.ts` can be replaced by a fetch without renaming anything downstream. `slug` and
 * `key` are the identifiers the site already matches on — that is the whole reason they are seeded
 * from the frontend rather than generated.
 *
 * Order is `position`, never alphabetical: every one of these lists is hand-ordered and the order
 * carries meaning.
 */
class TaxonomyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'topics' => Topic::ordered()->get()->map(fn (Topic $t): array => [
                'slug' => $t->slug,
                'name' => $t->name,
                'fullName' => $t->full_name,
                'eyebrow' => $t->eyebrow,
                'description' => $t->description,
            ]),
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
}
