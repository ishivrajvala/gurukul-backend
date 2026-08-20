<?php

namespace App\Providers;

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
         * The Avdhara stylesheet used to be injected here. It is now a COMPILED FILAMENT THEME at
         * `resources/css/filament/admin/theme.css`, registered on the panel itself — which is the
         * only way to change Tailwind tokens rather than paint over the compiled result.
         */

        //
    }
}
