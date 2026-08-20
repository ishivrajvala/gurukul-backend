<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of "most asked" — a question in the words a parent uses, pointing at the article
 * that answers it.
 *
 * THE QUESTION IS NOT THE ARTICLE'S TITLE, and that is the whole reason this table exists rather
 * than a boolean on `articles`. A title is written to be read once somebody has arrived; a question
 * is written to be recognised by somebody who has not. "Should my 5-year-old be reading?" and
 * "Should My Five-Year-Old Already Be Reading?" are the same subject phrased for two different
 * moments, and an editor needs to tune the first without touching the second.
 *
 * IT ALWAYS POINTS AT A REAL ARTICLE. A most-asked row is a link and nothing else, so a question
 * whose article is missing or unpublished is a dead end presented as an answer. `answered()` is
 * what the API filters on, and the admin screen requires the relation.
 */
class MostAsked extends Model
{
    protected $table = 'most_asked';

    protected $fillable = ['question', 'article_id', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Rows whose article exists and is published, in the editor's order.
     *
     * `whereHas` rather than a join: `published()` on Article already carries the status, the date
     * and the "not in the future" rule, and restating any of that here is how the two drift apart.
     */
    public function scopeAnswered(Builder $query): Builder
    {
        return $query
            ->whereHas('article', fn (Builder $q): Builder => $q->published())
            ->orderBy('position');
    }
}
