<?php

namespace App\Providers;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
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

        $this->configureTableDefaults();
    }

    /**
     * THE PANEL'S TABLE CONVENTIONS, SET ONCE.
     *
     * `configureUsing` is a default applied to every instance the application ever makes, and it is
     * why these live here rather than repeated across twenty-three resources. Twenty-three copies
     * of a convention is twenty-three chances for one to drift, and the drift is invisible —
     * nothing fails, one screen just quietly looks like a different application.
     *
     * Any of it can still be overridden on a single action where a screen genuinely needs something
     * else. A default is not a prohibition.
     */
    private function configureTableDefaults(): void
    {
        /*
         * THE NEW BUTTON BELONGS TO THE TABLE, NOT THE PAGE HEADER.
         *
         * It sits above the rows it creates, in the same strip as the search and the filters — the
         * controls for this table, in one place. In the page header it was a floating button whose
         * relationship to anything on screen had to be guessed, and on a page that also carries
         * navigation buttons (Articles has two) it read as a third link rather than the primary
         * action.
         *
         * The matching `Actions\CreateAction::make()` has been removed from each list page's
         * `getHeaderActions()`; without that removal the button would render in both places.
         *
         * GUARDED, because `configureUsing` fires for EVERY table in the application — relation
         * managers and widget tables included, and neither has a resource to create into.
         */
        Table::configureUsing(function (Table $table): void {
            $livewire = $table->getLivewire();

            if (! $livewire instanceof ListRecords) {
                return;
            }

            $resource = $livewire::getResource();

            /*
             * `canCreate()` is the resource's own answer and is respected exactly. Subscribers
             * returns false on purpose — consent is the asset on that list and nobody is typed onto
             * it by hand — so it must not grow a New button out of a global default.
             */
            if (! $resource::canCreate()) {
                return;
            }

            $table->headerActions([
                CreateAction::make()->label('New '.$resource::getModelLabel()),
            ]);
        });

        /*
         * ROW ACTIONS ARE ICONS.
         *
         * A row carrying two or three labelled buttons gets read twice: once for the record, once
         * for the controls. Open and Delete are the two most guessable icons in any admin, and
         * `iconButton()` keeps the label as BOTH the tooltip and the accessible name — so the words
         * are still there on hover and for a screen reader, they just stop competing with the data.
         *
         * DELETE KEEPS ITS CONFIRMATION. Making a destructive control smaller and quieter without
         * one would be straightforwardly worse; Filament's DeleteAction confirms by default and
         * nothing here changes that.
         */
        EditAction::configureUsing(fn (EditAction $action) => $action->iconButton());
        ViewAction::configureUsing(fn (ViewAction $action) => $action->iconButton());
        DeleteAction::configureUsing(fn (DeleteAction $action) => $action->iconButton());
    }
}
