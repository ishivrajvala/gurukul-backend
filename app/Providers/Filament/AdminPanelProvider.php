<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
	    ->login()
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
            ->brandName('Avdhara')
            /*
             * Nunito Sans is the body face on the site and the right one for an admin: it is a
             * reading face at small sizes, which is what a table of thirty rows is. Baloo 2 is the
             * display face and is deliberately NOT used here. The site's headings run 36-52px and a
             * panel is dense by necessity; the brand carries through colour and logo rather than by
             * making the admin look like a marketing page.
             */
            ->font('Nunito Sans')
            /*
             * One light palette, as on the site. Filament ships dark mode on by default, and a
             * half-themed dark mode looks broken; there is no dark palette in `tokens.ts` to theme
             * it from, so it is off rather than wrong.
             */
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ]);
    }
}
