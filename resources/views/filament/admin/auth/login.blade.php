{{--
    THE SIGN-IN FORM — the right-hand panel of `auth/layout.blade.php`.

    NOT `<x-filament-panels::page.simple>`, which is what Filament's own login view opens with.
    That component draws the logo, heading and subheading itself, in that order and that spacing,
    inside a centred card. This screen needs the logo at the top of a full-height panel with a
    two-tone heading under it, so the header is written out here rather than fought with from a
    stylesheet.

    What is NOT rewritten is the form. `{{ $this->form }}` and `getCachedFormActions()` are
    Filament's, so validation, the rate limiter, the failure message and the submit button all
    behave exactly as they do on the stock page — this file changes where they sit, not what they
    do. `App\Filament\Admin\Auth\Login` adds two placeholders and nothing else.

    One root element, because Livewire requires it.
--}}
<div class="av-auth-form">
    {{--
        The full lockup, in colour. `logo.webp` rather than the sidebar's `logo-white.webp`,
        because this panel is white. The brand name is the alt text, as it is in the sidebar.
    --}}
    <img
        src="{{ asset('images/logo.webp') }}"
        alt="{{ filament()->getBrandName() }}"
        class="av-auth-logo"
    />

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    {{--
        NO "FORGOT PASSWORD?" LINK, though the reference has one. This panel does not call
        `->passwordReset()`, so there is no route behind it and Filament renders nothing — a link
        added here by hand would be a dead one. This line is what somebody locked out can actually
        act on, and it is kept to ONE line on purpose: at three lines it pushed the panel past the
        viewport and put a scrollbar on a screen with two fields on it.
    --}}
    <div class="av-auth-help">
        <span class="av-auth-help-rule"></span>

        <p class="av-auth-help-text">Trouble signing in? Ask an administrator.</p>
    </div>

    {{--
        Filament's own simple page mounts this, so it is mounted here too. Nothing on this screen
        opens a modal today; it is what lets a form action added later open one at all, and its
        absence would look like the action was broken rather than like a missing mount point.
    --}}
    <x-filament-actions::modals />
</div>
