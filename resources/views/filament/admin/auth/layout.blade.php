{{--
    THE SIGN-IN LAYOUT — the photograph on the left, the form on the right, 60/40.

    A LAYOUT OF OUR OWN RATHER THAN FILAMENT'S `layout.simple`, and rather than an override of it
    in `resources/views/vendor/`. Filament's simple layout centres one card in the viewport; there
    is no arrangement of CSS that turns "one centred card" into "two full-height panels" without
    fighting it, and a copy of the vendor file under `vendor/` would silently keep rendering the
    Filament of the day it was copied after the next `composer update`.

    This builds on `layout.base` instead, which is the part worth inheriting: the head, the fonts,
    the compiled theme, `@filamentStyles`, `@filamentScripts`, Livewire and the notification stack
    all come from there, so none of it is restated here and none of it can drift.

    Applied by `App\Filament\Admin\Auth\Login` alone — it names this file in `$layout`. Nothing
    else in the panel is a SimplePage, so Filament's own layout is left exactly as it is.
--}}
<x-filament-panels::layout.base :livewire="$livewire">
    <div class="av-auth">
        {{--
            THE PHOTOGRAPH. Decorative, so it takes an EMPTY alt rather than a description: it
            carries no information the form does not, and a screen reader announcing a paragraph
            about a woman at a laptop is a paragraph between somebody and the two fields they came
            for. An empty alt is the correct way to say "skip this"; a MISSING one would make the
            same reader fall back to announcing the file name.

            Eager and high priority because it is the largest thing on the page and is above the
            fold on every screen wide enough to show it at all — lazy-loading the one image in the
            initial viewport only delays it.
        --}}
        <aside class="av-auth-brand">
            <img
                src="{{ asset('images/login-hero.webp') }}"
                alt=""
                class="av-auth-photo"
                loading="eager"
                fetchpriority="high"
            />
        </aside>

        {{-- THE FORM PANEL. `<main>` because on this page the form is the page. --}}
        <main class="av-auth-panel">
            {{--
                The mandala, low and in the corner, standing in for the botanical line-work in the
                reference. The reference's leaves are generic stock ornament; this is the brand's
                own shape, and the site already uses it exactly this way — as a watermark behind
                copy rather than as a picture of anything.
            --}}
            <div class="av-auth-art" aria-hidden="true">
                @include('filament.admin.auth.mandala')
            </div>

            <div class="av-auth-panel-inner">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire?->getRenderHookScopes()) }}
</x-filament-panels::layout.base>
