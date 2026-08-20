<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One section of a landing page.
 *
 * SIX TYPES, EACH BACKED BY A COMPONENT THAT ALREADY EXISTS on the site. This is not an open
 * builder: the site's architecture forbids redefining a shared component locally, and a page
 * assembled from arbitrary blocks is a page the design system has never seen. Adding a seventh type
 * is a deliberate change here AND in the frontend's renderer, which is the point.
 *
 * `data` holds that type's fields. Nothing queries inside it, the whole page is read at once by
 * slug, and the admin form already knows which fields belong to which type; what has to be
 * enforceable — order, published, which page — is columns.
 */
class LandingSection extends Model
{
    protected $fillable = ['landing_page_id', 'type', 'data', 'is_published', 'position'];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_published' => 'boolean', 'position' => 'integer'];
    }

    /**
     * The types the frontend can render, and what each one is for.
     *
     * The frontend has a matching switch. A type here that it does not know is skipped rather than
     * crashing the page, but it is also a section an editor published that nobody will ever see.
     */
    public const TYPES = [
        'hero' => 'Hero — eyebrow, headline, a paragraph, one button, one picture',
        'rich_text' => 'Text — a heading and formatted copy',
        'points' => 'Points — a heading and a grid of short titled paragraphs',
        'faq' => 'Questions — a heading and a list of question and answer pairs',
        'cta_band' => 'Call to action — an indigo band with a heading, a line and one button',
        'form' => 'Form — the waitlist or contact form, with a heading above it',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
