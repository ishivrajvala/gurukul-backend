{{--
    The sidebar brand.

    TWO IMAGES, ONE VISIBLE AT A TIME. Filament's `brandLogo` is a single picture, and a horizontal
    lockup squeezed into a 64px collapsed rail renders the wordmark as an illegible smear. So the
    full logotype is shown while the sidebar is open and the MARK alone once it collapses.

    The swap is CSS, not Alpine state: `.fi-sidebar` carries Filament's own collapsed class, so the
    two images are simply shown and hidden by it. Doing it in JavaScript would flash the wrong logo
    on every page load before Alpine boots.

    `logo-mark.webp` is cropped from the logotype itself rather than drawn again — a hand-made
    approximation of a brand mark is a second brand mark, and the two drift.
--}}
<img
    src="{{ asset('images/logo-white.webp') }}"
    alt="{{ filament()->getBrandName() }}"
    class="av-logo-full"
/>
<img
    src="{{ asset('images/logo-mark.webp') }}"
    alt=""
    aria-hidden="true"
    class="av-logo-mark"
/>
