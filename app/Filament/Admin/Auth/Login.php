<?php

namespace App\Filament\Admin\Auth;

use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BaseLogin;

/**
 * The panel's sign-in page.
 *
 * A TWO-PANEL SCREEN — the brand at full height on the left, the form on the right — in place of
 * Filament's single centred card. Everything that makes this page work is inherited: the rate
 * limiter, `authenticate()`, the `canAccessPanel` check that follows it, the failure message and
 * the session regeneration are all `Filament\Pages\Auth\Login`'s and are not touched here. Only
 * two things are overridden, and both are presentation.
 *
 * WHY A CUSTOM LAYOUT RATHER THAN CSS ON FILAMENT'S. `layout.simple` centres one `<main>` in the
 * viewport and sizes it from `getSimplePageMaxContentWidth()`. Turning that into two full-height
 * panels means undoing its flexbox, its max-width and its card chrome from a stylesheet, and every
 * one of those is a Filament class that can be renamed in a patch release. `auth/layout.blade.php`
 * builds on `layout.base` instead — the head, fonts, compiled theme, Livewire and scripts — which
 * is the part that is actually stable.
 *
 * @see \App\Providers\Filament\AdminPanelProvider  registers this page via `->login(...)`
 */
class Login extends BaseLogin
{
    /**
     * NOT under `Filament/Admin/Pages`, on purpose. The panel's `discoverPages()` scans that
     * directory, and a login page discovered as an ordinary page would be registered twice and
     * appear in the navigation as a menu item called "Login".
     *
     * @var view-string
     */
    protected static string $layout = 'filament.admin.auth.layout';

    /**
     * @var view-string
     */
    protected static string $view = 'filament.admin.auth.login';

    /**
     * A PLACEHOLDER, because this panel's field is an email address and the label alone does not
     * say so — people try their first name. The label stays Filament's translated string; this
     * only shows the shape of the thing underneath it.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->placeholder('you@gurukul2.com');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->placeholder('Your password');
    }
}
