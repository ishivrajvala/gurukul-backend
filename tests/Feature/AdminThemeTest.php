<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * The compiled admin theme is present, linked, and actually carries the brand.
 *
 * THE FAILURE THIS GUARDS AGAINST IS TOTAL AND SILENT. `->theme(asset(...))` REPLACES Filament's
 * own stylesheet rather than adding to it, so if `public/css/filament/admin/theme.css` is missing —
 * a fresh clone where nobody ran `npm run theme`, a deploy that skipped it, a `public/` that is
 * gitignored — the panel links a 404 and every page renders as unstyled HTML. Every request still
 * returns 200, so `AdminPagesRenderTest` passes throughout.
 *
 * It also checks the FONT IMPORT, because that failed once in a way nothing else would catch: CSS
 * requires `@import` before all other rules, so the Google Fonts line placed further down the
 * source was silently dropped by the build. The compiled file still said `font-family: 'Baloo 2'`
 * on every heading, the face was never fetched, and the panel quietly fell through to the system
 * sans while looking, to a test, entirely fine.
 */
class AdminThemeTest extends TestCase
{
    private const THEME = 'css/filament/admin/theme.css';

    public function test_the_compiled_theme_exists(): void
    {
        $path = public_path(self::THEME);

        $this->assertFileExists(
            $path,
            "The compiled theme is missing. Run `npm run theme`.\n"
            .'Without it the panel links a 404 and renders completely unstyled, while every page still returns 200.',
        );

        /* A truncated or empty build is the same outage with a file present. Filament's own compiled
           CSS is ~100KB and this is a superset of it. */
        $this->assertGreaterThan(
            50_000,
            filesize($path),
            'The compiled theme is suspiciously small — the build probably failed part way.',
        );
    }

    public function test_the_theme_carries_the_brand(): void
    {
        $css = file_get_contents(public_path(self::THEME));

        /* The two locked brand colours, as the panel's own `->colors()` also declares them. */
        $this->assertStringContainsStringIgnoringCase('#27156b', $css, 'indigo is missing from the theme');
        $this->assertStringContainsStringIgnoringCase('f7b75f', $css, 'marigold is missing from the theme');

        /*
         * The display face has to be FETCHED, not just named. `->font()` loads one family (Nunito
         * Sans); Baloo comes from the `@import` at the top of the source, and a build that drops it
         * leaves every heading naming a font nobody downloaded.
         */
        $this->assertStringContainsString(
            'fonts.googleapis.com',
            $css,
            'the Baloo 2 @import did not survive the build — it must be the FIRST rule in theme.css',
        );
        $this->assertStringContainsStringIgnoringCase('Baloo', $css, 'nothing sets the display face');
    }

    public function test_the_panel_links_the_theme_and_the_real_logo(): void
    {
        $user = User::first();

        $this->assertNotNull($user, 'no user to act as — seed one first');

        $html = $this->actingAs($user)->get(Filament::getPanel('admin')->getUrl())->getContent();

        $this->assertStringContainsString(
            self::THEME,
            $html,
            'the panel is not linking the compiled theme',
        );

        /* A wordmark set in whatever face loaded is not a logo. This is the same file the site's
           own nav uses, in the white variant, because the sidebar is indigo. */
        $this->assertStringContainsString(
            'logo-white.webp',
            $html,
            'the panel is not rendering the real logo',
        );

        $this->assertFileExists(
            public_path('images/logo-white.webp'),
            'the logo the panel points at is not on disk',
        );
    }
}
