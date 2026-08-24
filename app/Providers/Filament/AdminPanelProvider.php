<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
	    ->default()
            ->id('admin')
            ->domain(app()->environment('production') ? 'admin.gurukul2.com' : null)
	    ->path('')
	    /*
             * THE SIGN-IN PAGE, ours rather than Filament's: a two-panel screen with the brand at
             * full height beside the form, instead of one card centred in an empty field. The class
             * inherits every piece of the authentication itself — see its docblock for what is and
             * is not overridden.
             */
	    ->login(\App\Filament\Admin\Auth\Login::class)
            /*
             * The Avdhara palette, from the frontend's locked `models/tokens.ts`. Six brand tokens
             * and nothing else; a shade that does not exist there is an opacity of one that does.
             * `Color::hex()` generates the 50-950 ramp Filament needs from each single value.
             *
             * DANGER STAYS RED. Brand orange (#F0713D) is close enough to a warning colour to be
             * tempting here, and it is the wrong call: people expect red for destructive actions,
             * and a delete button in Avdhara orange gets pressed by accident. Brand colour belongs
             * on the panel, not on the one control where a mistake cannot be undone.
             */
            ->colors([
                'primary' => Color::hex('#27156B'),   // indigo
                'warning' => Color::hex('#F7B75F'),   // marigold
                'success' => Color::hex('#56A195'),   // green
                'info' => Color::hex('#27156B'),      // indigo again: there is no fifth brand hue
                'danger' => Color::Red,
                'gray' => Color::Slate,
            ])
            /*
              * THE REAL LOGO, the same file the site's nav uses.
              *
              * `brandName` alone renders the word "Avdhara" in whatever face the panel loads, which
              * is a wordmark pretending to be a logo. The white variant because the sidebar is
              * indigo — the site makes exactly this choice in its own nav, on the same colour.
              *
              * `brandName` stays as the accessible name and the browser-tab title; the logo is the
              * picture, and an image with no name is a link a screen reader reads as "image".
              */
             ->brandName('Avdhara')
             /*
              * A BLADE VIEW RATHER THAN `brandLogo`, because the sidebar needs two logos.
              *
              * `brandLogo` takes one picture, and a horizontal lockup squeezed into the 64px
              * collapsed rail renders the wordmark as an illegible smear. The view shows the full
              * logotype while the sidebar is open and the mark alone once it collapses; the swap is
              * CSS keyed off Filament's own collapsed class, so nothing flashes before Alpine boots.
              */
             ->brandLogo(fn () => view('filament.admin.brand'))
             /*
              * THE WRAPPER'S HEIGHT, not the picture's. Filament puts this on `.fi-logo` as an
              * inline style, and it defaults to 1.5rem — which clips the logo however tall the
              * <img> inside says it is. It has to match the CSS, so both are 4.25rem.
              */
             ->brandLogoHeight('5rem')
             /*
              * A REAL .ICO, not the logotype. `logo.webp` is a 420x175 horizontal lockup, and a
              * browser asked to draw it in a 16px square renders the wordmark as two grey smudges.
              * This is the MARK alone — cropped from the same master the site's icons come from —
              * on a white ground, at 16/32/48 in one file so the browser picks its own size.
              *
              * White rather than transparent: the artwork is dark line-work, and a transparent
              * favicon vanishes into a dark tab strip.
              */
             ->favicon(asset('images/favicon.ico'))
            /*
             * BOTH FACES, each doing its own job.
             *
             * `->font()` sets the panel's BASE, and it is Baloo 2 — the brand's own face, worn by
             * everything: headings, labels, table cells, buttons, numbers, navigation.
             *
             * NUNITO SANS IS NOT GONE, it is reduced to one job: description and helper text, the
             * sentences somebody reads rather than scans. That is applied in the compiled theme
             * rather than here, because `->font()` loads exactly one family.
             *
             * THE TRADE WAS MADE ON PURPOSE AND IS LOCKED. Baloo is a rounded display face and it
             * is genuinely mushier than Nunito at 13px; the panel used to split them for that
             * reason. The answer is size, not a second face — nothing here is 13px any more. See
             * the font block in `resources/css/filament/admin/tailwind.config.js`.
             */
            ->font('Baloo 2')
            /*
             * One light palette, as on the site. Filament ships dark mode on by default, and a
             * half-themed dark mode looks broken; there is no dark palette in `tokens.ts` to theme
             * it from, so it is off rather than wrong.
             */
            ->darkMode(false)
            /*
             * THE COMPILED THEME. Built with the Tailwind v3 CLI into `public/`, not through this
             * project's Vite — Filament 3 ships a v3 preset and the app's own `app.css` is on
             * Tailwind v4, and two majors cannot share a build. `npm run theme` rebuilds it; see
             * `resources/css/filament/admin/tailwind.config.js`.
             *
             * `->theme(asset(...))` rather than `->viteTheme(...)` for the same reason: the output
             * is a plain built file with no Vite manifest entry to look up.
             *
             * THIS REPLACES A STYLESHEET INJECTED THROUGH `FilamentAsset`. That could only paint
             * over Filament's compiled CSS from outside — it could not change a single Tailwind
             * token, so radii, shadows, spacing and ring colours all stayed Filament's while only
             * type and a few colours were Avdhara's.
             */
            ->theme(asset('css/filament/admin/theme.css'))
            /*
             * A DELIBERATE GROUP ORDER, not alphabetical.
             *
             * What arrives from the site comes first, because somebody waiting is the only thing on
             * here with a cost attached and that cost falls on them. Then the content people edit
             * weekly, then the things that change once a season, then settings. Alphabetical put
             * Careers above Inbox and Taxonomy in the middle of the editorial work.
             */
            ->navigationGroups(array_map(
                /*
                 * NOT COLLAPSIBLE, and that is a fix rather than a preference.
                 *
                 * Filament's groups collapse by default and remember it, so the sidebar could open
                 * with all nine shut — nine labels, nine chevrons, every item behind a click. Worse,
                 * a collapsed group still reserved its full height, so the rail became mostly empty
                 * space with a heading floating in the middle of it.
                 *
                 * Twenty-two items across nine groups fits a laptop once the spacing is right. A
                 * navigation you have to open before you can read it is slower than a slightly
                 * longer one you can scan.
                 */
                fn (string $label): NavigationGroup => NavigationGroup::make($label)->collapsible(false),
                [
                    'Inbox',
                    /*
                     * THE EMAIL LIST IS ITS OWN GROUP, directly under the inbox and deliberately
                     * not in it. Subscribers used to be the fifth tab of Enquiries, sitting in the
                     * same pending count as four things that all needed doing — but nobody handles
                     * a subscriber, so every one of them read as work that could never be finished
                     * and made the number meaningless. An inbox answers "what is waiting for me";
                     * this answers "who gets Thursday's email".
                     */
                    'Email list',
                    /* Everything written and published: articles, landing pages, the top strip. */
                    'Content System',
                    /* Circles and Stories merged: both are the parenting community, and two groups
                       of two and three read as more structure than there is. */
                    'Parenting',
                    'Careers',
                    'Taxonomy',
                    /* Settings also holds the homepage bands: they are configuration for one page,
                       edited rarely, and a group of two was carrying more weight in the menu than
                       the job deserves. */
                    'Settings',
                ],
            ))
            /*
             * The sidebar collapses to icons on desktop. Twenty-two resources is a long column, and
             * somebody working inside one record for an hour should be able to give the width back
             * to the thing they are editing.
             */
            ->sidebarCollapsibleOnDesktop()
            /*
             * 16.25rem, matching the reference. Filament's default is 20rem, which on a laptop is
             * a fifth of the screen given to nine words repeated down a column — the longest label
             * here is "Community reviews" and it fits with room to spare.
             */
            ->sidebarWidth('16.25rem')
            /*
             * NO BREADCRUMBS.
             *
             * "Enquiries > List" sat directly above a heading reading "Enquiries", with the sidebar
             * item already highlighted — the same word three times before a single record. They
             * earn their place in a deep tree; this panel is one level, list and edit, and a
             * breadcrumb that can only ever say where you obviously are is furniture.
             */
            ->breadcrumbs(false)
            /*
             * GLOBAL SEARCH, in the header.
             *
             * It is off until a resource says what it can be found BY, which is why there was no
             * search field: `getGloballySearchableAttributes` on each resource is what turns it on.
             * Twenty-two resources is past the point where the way to reach a record is to remember
             * which list it is in.
             */
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            /*
             * NOTIFICATIONS, in the header, and they are real rather than a bell.
             *
             * Every submission the site takes raises one (see `NotifyAdmins`), so somebody with the
             * panel open learns that a family has asked to join a Circle without refreshing a list
             * they were not looking at. An inbox nobody watches is the failure this whole section
             * of the admin is shaped around.
             */
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            /*
             * THE HEADER MENU. Filament's topbar carried only the account dropdown, so the one
             * thing an editor constantly wants — to look at the page they have just changed — meant
             * typing the address by hand.
             *
             * A NEW TAB, always. The panel is a tool you work in with the site open beside it, and
             * replacing this tab would throw away a half-filled form with no warning.
             */
            ->userMenuItems([
                'site' => MenuItem::make()
                    ->label('View the site')
                    ->url(fn (): string => (string) config('app.frontend_url'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
                /*
                 * The two-factor pages. Registered so they get panel routes and the panel's chrome;
                 * neither appears in the navigation — they are somewhere you are SENT, never
                 * somewhere you browse to.
                 */
                \App\Filament\Admin\Auth\TwoFactorSetup::class,
                \App\Filament\Admin\Auth\TwoFactorChallenge::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            /*
             * THE STOCK WIDGETS ARE GONE. `AccountWidget` told you who you were signed in as, and
             * `FilamentInfoWidget` advertised the framework — neither answers the question somebody
             * opens this panel to ask, which here is always "has anybody been in touch".
             */
            ->widgets([
                \App\Filament\Admin\Widgets\InboxOverview::class,
                /*
                 * THE SHAPE, under the numbers. The stats answer "is anybody waiting on me"; only a
                 * trend answers "is this quieter than usual", which is how a form that silently
                 * stopped posting gets noticed at all.
                 */
                \App\Filament\Admin\Widgets\ArrivalsChart::class,
                \App\Filament\Admin\Widgets\RecentSubmissions::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                /*
                 * TWO-FACTOR IS MANDATORY, FOR EVERY ROLE. On `authMiddleware` rather than in the
                 * login page, because login is not the only way a session comes to exist — a
                 * remembered cookie, a deep link, or any future SSO route would all walk past a
                 * check that lived in one controller. Here it runs on every authenticated request.
                 */
                \App\Http\Middleware\RequireTwoFactor::class,
            ]);
    }
}
