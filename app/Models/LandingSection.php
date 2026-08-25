<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One section of a landing page.
 *
 * COMPONENT + VARIANT + DATA. What the section is for, how it is laid out, and what goes inside it.
 * Those change for different reasons — an editor reorders a page by component, redesigns it by
 * variant, and rewrites it by data — so they are three fields rather than one.
 *
 * STILL NOT AN OPEN BUILDER. Twenty-nine components and sixteen layouts is a wide vocabulary, but a
 * closed one: every layout is a shape the site already draws, from components that already exist in
 * its design system. The frontend's `models/landingComponents.ts` is the matching registry and the
 * two must be changed together — a component here that it does not know is skipped rather than
 * crashing the page, but it is also a section an editor published that nobody will ever see.
 *
 * `data` holds the fields. Nothing queries inside it, the whole page is read at once by slug, and
 * the admin form already knows which fields matter to which component; what has to be enforceable —
 * order, published, which page — is columns.
 *
 * `type` IS THE OLD FIELD, kept as history. See the migration that split it.
 */
class LandingSection extends Model
{
    protected $fillable = ['landing_page_id', 'component', 'variant', 'type', 'data', 'is_published', 'position'];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_published' => 'boolean', 'position' => 'integer'];
    }

    /**
     * The sixteen layouts, and what each one is.
     *
     * THIS IS THE WHOLE DESIGN SURFACE AN EDITOR HAS. Deliberately small: each is a shape the site
     * already uses, so a landing page cannot invent a layout the design system has never seen —
     * which is what a free-form page builder guarantees.
     */
    public const VARIANTS = [
        'image-only' => 'Image only',
        'content-only' => 'Content only',
        'image-left' => 'Image left',
        'image-right' => 'Image right',
        'image-points' => 'Image + points',
        'content-points' => 'Content + points',
        'full-width' => 'Full width',
        'full-bleed' => 'Full-bleed image',
        'overlay' => 'Content over image',
        'cards' => 'Cards',
        'grid' => 'Grid',
        'carousel' => 'Carousel',
        'gallery' => 'Gallery',
        'video' => 'Video',
        'timeline' => 'Timeline',
        'interactive' => 'Interactive',
    ];

    /**
     * The twenty-nine components, each with the layouts it may use.
     *
     * ORDERED AS A PAGE IS BUILT, not alphabetically: opening, then the argument, then the
     * framework, then the activity detail, then the ways out. An editor scanning this list is
     * usually looking for "what comes next", and alphabetical would put the call to action third.
     *
     * THE FIRST VARIANT IN EACH LIST IS THE DEFAULT, and is what a section falls back to if its
     * stored variant is ever removed from the list. Mirrors `LANDING_COMPONENTS` on the frontend.
     */
    public const COMPONENTS = [
        'hero' => [
            'label' => 'Hero',
            'purpose' => 'The opening. One message, and the reason to keep reading.',
            'variants' => ['image-right', 'content-only', 'image-left', 'full-bleed', 'overlay', 'video', 'image-only', 'interactive'],
        ],
        'content' => [
            'label' => 'Content / Introduction',
            'purpose' => 'General explanation or storytelling. The workhorse.',
            'variants' => ['content-only', 'image-right', 'image-left', 'image-points', 'content-points', 'image-only', 'full-width'],
        ],
        'parent_reality' => [
            'label' => 'Parent Reality / Problem',
            'purpose' => 'Name what a parent is living with, without alarm.',
            'variants' => ['image-right', 'content-only', 'cards', 'content-points', 'image-left', 'image-only'],
        ],
        'promise' => [
            'label' => 'Promise / Outcomes',
            'purpose' => 'What the child and the family actually gain.',
            'variants' => ['cards', 'content-points', 'image-points', 'grid', 'timeline', 'image-right'],
        ],
        'how_it_works' => [
            'label' => 'How It Works / Process',
            'purpose' => 'Three to five steps, in order, without jargon.',
            'variants' => ['timeline', 'cards', 'grid', 'content-points', 'image-points', 'interactive'],
        ],
        'nine_petals' => [
            'label' => '9 Petals',
            'purpose' => 'Whole-child development, as the framework the site already draws.',
            'variants' => ['interactive', 'grid', 'cards', 'image-points', 'content-points'],
        ],
        'pathway_selector' => [
            'label' => 'Age / Pathway Selector',
            'purpose' => 'Send a parent to the stage their child is actually at.',
            'variants' => ['cards', 'grid', 'carousel', 'timeline', 'interactive'],
        ],
        'development_pathway' => [
            'label' => 'Development Pathway',
            'purpose' => 'How one capacity grows, stage by stage.',
            'variants' => ['timeline', 'cards', 'image-points', 'grid', 'interactive'],
        ],
        'mandala_showcase' => [
            'label' => 'Mandala Showcase',
            'purpose' => 'Show Living Education as something that happens, not a claim.',
            'variants' => ['image-right', 'cards', 'carousel', 'video', 'image-only', 'interactive'],
        ],
        'wisdom_science' => [
            'label' => 'Ancient Wisdom + Modern Science',
            'purpose' => 'Where the two agree, stated at the strength the evidence supports.',
            'variants' => ['content-points', 'cards', 'image-right', 'grid', 'image-left'],
        ],
        'comparison' => [
            'label' => 'Comparison',
            'purpose' => 'A difference, made plain and fairly.',
            'variants' => ['cards', 'grid', 'content-points', 'timeline', 'image-points'],
        ],
        'story' => [
            'label' => 'Story / Testimonial',
            'purpose' => 'A family in their own words.',
            'variants' => ['image-right', 'content-only', 'cards', 'carousel', 'video', 'full-bleed'],
        ],
        'activity_list' => [
            'label' => 'Activity List',
            'purpose' => 'A set of things to do, to browse.',
            'variants' => ['grid', 'cards', 'carousel', 'content-points'],
        ],
        'activity_overview' => [
            'label' => 'Activity Overview',
            'purpose' => 'Introduce one activity: what it is and why it matters.',
            'variants' => ['image-right', 'image-only', 'video', 'content-only', 'content-points'],
        ],
        'materials' => [
            'label' => 'Materials',
            'purpose' => 'What is needed, and what will do instead.',
            'variants' => ['content-points', 'grid', 'image-points', 'cards', 'image-only'],
        ],
        'activity_steps' => [
            'label' => 'How-To / Activity Steps',
            'purpose' => 'How to actually do it.',
            'variants' => ['timeline', 'cards', 'carousel', 'video', 'grid', 'interactive'],
        ],
        'gallery' => [
            'label' => 'Image / Media Gallery',
            'purpose' => 'Show it rather than describe it.',
            'variants' => ['gallery', 'carousel', 'image-only', 'full-bleed', 'video', 'grid'],
        ],
        'parent_guidance' => [
            'label' => 'Parent Guidance',
            'purpose' => 'What the adult does — which is usually less than they expect.',
            'variants' => ['content-points', 'cards', 'image-points', 'grid', 'image-right'],
        ],
        'what_to_notice' => [
            'label' => 'What to Notice',
            'purpose' => 'Turn a parent into an observer of their own child.',
            'variants' => ['content-points', 'cards', 'grid', 'image-points'],
        ],
        'what_to_avoid' => [
            'label' => 'What to Avoid / Instead Try',
            'purpose' => 'Paired guidance, without making a parent feel caught out.',
            'variants' => ['cards', 'content-points', 'grid', 'timeline'],
        ],
        'activity_variations' => [
            'label' => 'Activity Variations',
            'purpose' => 'One experience, adapted — by age, by space, by what is in the house.',
            'variants' => ['cards', 'grid', 'content-points', 'carousel', 'timeline'],
        ],
        'seasonal' => [
            'label' => 'Seasonal Collection',
            'purpose' => 'What this time of year is good for.',
            'variants' => ['image-right', 'cards', 'grid', 'carousel', 'full-bleed', 'gallery'],
        ],
        'festival' => [
            'label' => 'Festival Experience',
            'purpose' => 'A festival as something a family does together, not a fact to learn.',
            'variants' => ['image-right', 'cards', 'timeline', 'gallery', 'carousel', 'content-points'],
        ],
        'wisdom_story' => [
            'label' => 'Story / Wisdom / Cultural Content',
            'purpose' => 'Bharatiya knowledge, told rather than asserted.',
            'variants' => ['content-only', 'image-right', 'carousel', 'video', 'full-width', 'gallery'],
        ],
        'resources' => [
            'label' => 'Resources / Downloads',
            'purpose' => 'Something to take away.',
            'variants' => ['cards', 'grid', 'content-points', 'image-right'],
        ],
        'related' => [
            'label' => 'Related Content',
            'purpose' => 'Where to go next, before the page runs out.',
            'variants' => ['cards', 'carousel', 'grid', 'content-points'],
        ],
        'faq' => [
            'label' => 'FAQ',
            'purpose' => 'The questions asked before somebody commits.',
            'variants' => ['content-only', 'cards', 'grid'],
        ],
        'cta' => [
            'label' => 'Call to action',
            'purpose' => 'One clear next step. One per page.',
            'variants' => ['content-only', 'image-right', 'full-width', 'cards', 'overlay'],
        ],
        'discovery' => [
            'label' => 'Search / Filter / Discovery',
            'purpose' => 'Let a parent find their own way in.',
            'variants' => ['interactive', 'cards', 'grid'],
        ],
    ];

    /** `key => label`, for the component picker. */
    public static function componentOptions(): array
    {
        return array_map(fn (array $c): string => $c['label'], self::COMPONENTS);
    }

    /**
     * The variants one component allows, as `key => label`.
     *
     * Returns every variant for an unknown component rather than an empty list: a section whose
     * component has been retired should still let an editor see and change its layout, instead of
     * presenting a dropdown with nothing in it and no way out.
     *
     * `array_intersect_key` against `VARIANTS` keeps the canonical ORDER and label rather than the
     * order they happen to be listed against the component, so the dropdown reads the same
     * everywhere.
     */
    public static function variantOptions(?string $component): array
    {
        $allowed = self::COMPONENTS[$component]['variants'] ?? array_keys(self::VARIANTS);

        return array_intersect_key(self::VARIANTS, array_flip($allowed));
    }

    /** Where a component starts, and what an invalid stored variant falls back to. */
    public static function defaultVariant(?string $component): ?string
    {
        return self::COMPONENTS[$component]['variants'][0] ?? null;
    }

    /** One line under the picker, saying what the chosen component is for. */
    public static function purposeOf(?string $component): ?string
    {
        return self::COMPONENTS[$component]['purpose'] ?? null;
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}
