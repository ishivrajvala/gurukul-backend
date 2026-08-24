<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\Admin\Auth\TwoFactorChallenge;
use App\Filament\Admin\Auth\TwoFactorSetup;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TWO-FACTOR IS MANDATORY FOR EVERY PANEL ACCOUNT. This is what makes it mandatory.
 *
 * A setting nobody is forced through is a setting almost nobody turns on, and the accounts that
 * skip it are the ones worth attacking. So there is no opt-out and no grace period: an account
 * without a confirmed second factor cannot reach anything except the page that sets one up.
 *
 * ── WHY MIDDLEWARE RATHER THAN A CHECK IN THE LOGIN PAGE ──────────────────────────────────
 *
 * Because the login page is not the only way in. A session can be restored from a cookie, a link
 * can be opened straight to a deep URL, and a future SSO or magic-link route would bypass a check
 * that lived in one controller. This sits on the panel's `authMiddleware`, so it runs on every
 * authenticated request to every page, however that session came to exist.
 *
 * ── THE TWO STATES ────────────────────────────────────────────────────────────────────────
 *
 *   · NOT ENROLLED  → forced to setup. Cannot be skipped.
 *   · ENROLLED, this session not yet verified → forced to the challenge.
 *
 * The verified flag lives in the SESSION, not on the user, so it expires with the session rather
 * than making a device trusted for ever.
 */
class RequireTwoFactor
{
    /** The session key holding "this session has passed the challenge". */
    public const VERIFIED = 'auth.two_factor_verified';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return $next($request);
        }

        /*
         * The setup and challenge pages must stay reachable, or this redirects to a page it is
         * itself blocking. Livewire's own endpoint is exempt for the same reason: those pages are
         * Livewire components and their updates post to `/livewire/update`, which would otherwise
         * be redirected mid-interaction and break the form the person is being forced to use.
         */
        if ($this->isExempt($request)) {
            return $next($request);
        }

        /*
         * A VERIFIED SESSION IS CHECKED FIRST, and it is not a shortcut around enrolment.
         *
         * This flag is only ever set in two places — confirming enrolment, and passing the
         * challenge — and both require a code the person's own authenticator produced. So a
         * verified session already IMPLIES an enrolled account, and re-deriving that on every
         * request costs a decrypt of the secret for no added safety.
         *
         * It also keeps the common case cheap: this middleware runs on every authenticated request
         * to every page in the panel.
         */
        if ($request->session()->get(self::VERIFIED) === true) {
            return $next($request);
        }

        if (! $user->twoFactorEnabled()) {
            return redirect()->to(TwoFactorSetup::getUrl());
        }

        return redirect()->to(TwoFactorChallenge::getUrl());
    }

    private function isExempt(Request $request): bool
    {
        return $request->routeIs('filament.admin.pages.two-factor-setup')
            || $request->routeIs('filament.admin.pages.two-factor-challenge')
            || $request->routeIs('filament.admin.auth.logout')
            || $request->is('livewire/*');
    }
}
