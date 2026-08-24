<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\HomeConcern;
use App\Models\HomeStat;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The homepage's editable bands.
 *
 * ONE CALL FOR THE WHOLE SECTION. The figures and the concern cards are two tables because they are
 * two different things to write, but they are one band on the page and neither reads correctly
 * without the other. Two endpoints would be two round trips for one screenful.
 *
 * `/v1/stories-home` stays separate: that is a selection out of the story library rather than copy
 * belonging to the homepage, and it is the same rows Parent Stories serves.
 */
class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stats' => HomeStat::published()->get()->map(fn (HomeStat $s): array => [
                    'figure' => $s->figure,
                    'description' => $s->description,
                    'icon' => $s->icon,
                    /* `source` is deliberately NOT sent. It is a note to the editor so a figure can
                       be checked rather than inherited; the page has never shown one, and quietly
                       starting to would change what this band claims. */
                ]),

                'concerns' => HomeConcern::published()->get()->map(fn (HomeConcern $c): array => [
                    'eyebrow' => $c->eyebrow,
                    'title' => $c->title,
                    'bullets' => $c->bullets ?? [],
                    'cta' => $c->cta,
                    'ctaHref' => $c->cta_href,
                    'icon' => $c->icon,
                    'image' => $this->imageUrl($c->image_path),
                    'imageAlt' => $c->image_alt,
                ]),
            ],
        ]);
    }

    /**
     * The marigold strip above the nav.
     *
     * ITS OWN ENDPOINT, not part of `/home`, because the strip lives in the site's ROOT LAYOUT and
     * is therefore on every page. Folding it into the homepage payload would make every route on
     * the site fetch the homepage's figures and concern cards to draw one line of text.
     *
     * ONE STRIP, ALREADY CHOSEN. `live()` applies the published flag and both date bounds and
     * orders by priority, so the consumer gets the winner or null. Sending all of them and letting
     * the client choose would put the scheduling rule in two places, and the client is the copy
     * that goes stale.
     */
    /**
     * The public profiles, in display order, and ONLY the ones with a URL.
     *
     * Filtering here rather than on the website is deliberate. If the API returned every platform
     * and left the website to skip the blank ones, then every consumer — the site, the app, an
     * email footer — would have to remember to do it, and the first one that forgot would render an
     * icon linking nowhere. An empty platform is not data anybody needs.
     *
     * `platform` is a stable slug and the website maps it to an icon. `label` is what a person
     * reads, and may be renamed freely (twitter -> "X") without the icon disappearing.
     */
    public function socialLinks(): JsonResponse
    {
        return response()->json([
            'data' => SocialLink::live()->get()->map(fn (SocialLink $link): array => [
                'platform' => $link->platform,
                'label' => $link->label,
                'url' => $link->url,
            ])->all(),
        ]);
    }

    public function announcement(Request $request): JsonResponse
    {
        /*
         * `?audience=web` or `app`, defaulting to the website.
         *
         * The strip is served by an API and the site is not the only thing that will read it. A
         * strip marked `all` matches whatever is asked for, so every row that existed before this
         * parameter behaves exactly as it did — the only safe default for a filter added to live
         * content. Anything unrecognised falls back to `web` rather than returning nothing: a
         * mistyped query should not silently empty the top of the website.
         */
        $audience = in_array($request->query('audience'), ['web', 'app'], true)
            ? (string) $request->query('audience')
            : 'web';

        $strip = Announcement::live()->forAudience($audience)->with('landingPage')->first();

        return response()->json([
            'data' => $strip ? [
                'message' => $strip->message,
                'ctaLabel' => $strip->cta_label,
                'ctaHref' => $strip->ctaHref(),
                /* The consumer draws an icon from this. It never carries a colour. */
                'theme' => $strip->theme,
            ] : null,
        ]);
    }

    /**
     * An uploaded image becomes an absolute URL; a seeded one stays the path it already was.
     *
     * The three illustrations that shipped with the site live in the frontend's own `public/` as
     * `/assets/story-screens.webp`, and rewriting those into API URLs would break them for a build
     * that falls back. Anything an editor uploads goes to the public disk and needs a full URL,
     * because the consumer is a different origin and cannot resolve a bare path.
     */
    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
