<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * The Avdhara theme, registered as an asset rather than compiled as a Filament theme:
         * Filament 3 scaffolds Tailwind v3 and this project is on v4. See the file's own note.
         *
         * `filament:assets` publishes it; it is served after Filament's own stylesheet, which is
         * what lets plain selectors override the framework without `!important` everywhere.
         */
        FilamentAsset::register([
            Css::make('avdhara-theme', resource_path('css/filament-avdhara.css')),
        ]);

        //
    }
}
