<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The marigold strip above the nav.
 *
 * INDIGO TEXT ONLY, and nothing here can change that — it is a locked brand rule enforced in the
 * view, because white on marigold is about 1.9:1 and orange on marigold about 1.3:1. An editor
 * writes the words; the colours are not on offer.
 *
 * NO URGENCY AND NO SCARCITY. The site's voice rules ban both outright, which is why the standing
 * strip says how many families are being welcomed rather than how few places remain. "Only 3 left",
 * a countdown or a deadline all belong to a different company.
 */
class Announcement extends Model
{
    /**
     * What kind of strip this is.
     *
     * IT PICKS THE ICON, NOT THE COLOUR. Marigold with indigo text is locked — white on marigold is
     * about 1.9:1 and orange on it about 1.3:1 — so a festival strip is not a different palette, it
     * is a lamp instead of a circle. Adding a theme means a line here and an icon on the consumer.
     *
     * It is also how you find things: come next October, "every Diwali strip we have run" is a
     * filter rather than a scroll.
     */
    public const THEMES = [
        'news' => 'Announcement',
        'offer' => 'Offer',
        'festival' => 'Festival (Diwali, Holi, Pongal)',
        'season' => 'Season (summer, new term)',
        'special_day' => "Special day (Teachers' Day, Children's Day)",
    ];

    /**
     * Which surface should show it.
     *
     * The strip is served by an API and the website is not the only thing that will read it. "The
     * summer Circle opens in June" is for parents browsing the site; an app belonging to a family
     * already inside a paid programme should not be sold the thing they have bought.
     */
    public const AUDIENCES = [
        'all' => 'Everywhere',
        'web' => 'Website only',
        'app' => 'App only',
    ];

    protected $fillable = [
        'label', 'message', 'cta_label', 'theme', 'audience',
        'cta_type', 'cta_route', 'landing_page_id', 'cta_url',
        'utm_campaign', 'utm_source',
        'starts_at', 'ends_at', 'is_published', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Published, and inside its window right now.
     *
     * A NULL BOUND MEANS UNBOUNDED, which is what makes a standing strip and a seasonal one the
     * same kind of row: no start is "already running", no end is "until somebody stops it".
     */
    /**
     * Narrow to one surface.
     *
     * `all` always matches, so a strip nobody has thought about targeting behaves exactly as it did
     * before this column existed — which is the only safe default for a field added to live rows.
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->whereIn('audience', ['all', $audience]);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('priority')
            ->orderByDesc('starts_at');
    }

    /**
     * Where the strip's link goes, as one resolved href.
     *
     * The consumer gets a string and never has to know which of the three kinds it was. A landing
     * page resolves through the relation rather than a stored path, so renaming its slug moves the
     * link with it instead of breaking it silently.
     */
    public function ctaHref(): ?string
    {
        $href = match ($this->cta_type) {
            'landing' => $this->landingPage ? '/'.$this->landingPage->slug : null,
            'url' => $this->cta_url,
            default => $this->cta_route,
        };

        return $this->withCampaign($href);
    }

    /**
     * Append this strip's campaign to its own link, so the leads it produces are attributable to it.
     *
     * WITHOUT THIS THE CAMPAIGN FIELDS WOULD BE DECORATION. A strip that ran for six weeks and the
     * leads it produced are otherwise two facts with nothing joining them — the lead carries
     * whatever UTM happened to be on the link the visitor arrived by, which for somebody who
     * clicked a banner on our own site is nothing at all.
     *
     * `utm_source` DEFAULTS TO `site`, because a strip on our own pages IS the source and leaving it
     * empty would file these leads as Direct — indistinguishable from somebody typing the address in.
     *
     * EXISTING QUERY STRINGS ARE PRESERVED. A CTA already carrying `?tab=summer` must keep it;
     * blindly appending `?utm_campaign=` would produce two question marks and a dead link.
     */
    private function withCampaign(?string $href): ?string
    {
        if ($href === null || blank($this->utm_campaign)) {
            return $href;
        }

        /* An absolute URL to somewhere else is not ours to tag. */
        if (str_starts_with($href, 'http') && ! str_contains($href, (string) parse_url((string) config('app.frontend_url'), PHP_URL_HOST))) {
            return $href;
        }

        $params = http_build_query([
            'utm_source' => $this->utm_source ?: 'site',
            'utm_medium' => 'announcement',
            'utm_campaign' => $this->utm_campaign,
        ]);

        return $href.(str_contains($href, '?') ? '&' : '?').$params;
    }
}
