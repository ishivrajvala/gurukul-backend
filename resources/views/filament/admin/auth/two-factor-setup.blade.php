{{--
    Enrolment. Reached by anybody without a confirmed second factor, and not skippable.

    Uses the same two-panel auth layout as the login screen, so being sent here does not feel like
    being thrown out of the product — it is the same door, one step further in.
--}}
<div class="av-auth-form">
    <img src="{{ asset('images/logo.webp') }}" alt="{{ filament()->getBrandName() }}" class="av-auth-logo" />

    @if ($confirmed)
        {{--
            THE CODES ARE SHOWN ONCE AND ONLY ONCE. They are hashed, so this is not a policy — after
            this render nobody, including us, can read them again.
        --}}
        <div class="av-auth-heading">
            <h1 class="av-auth-title">Save these</h1>
            <p class="av-auth-subtitle">Your recovery codes.</p>
        </div>

        <p class="av-2fa-note">
            If you lose your phone, one of these gets you back in. Each works once. This is the only
            time they can be shown — keep them somewhere safe and not on the same phone.
        </p>

        <ul class="av-2fa-codes">
            @foreach ($recoveryCodes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>

        <x-filament::button wire:click="finish" class="av-2fa-submit">
            I have saved them
        </x-filament::button>
    @else
        <div class="av-auth-heading">
            <h1 class="av-auth-title">One more step</h1>
            <p class="av-auth-subtitle">Two-factor is required for every account here.</p>
        </div>

        <p class="av-2fa-note">
            This panel holds every family who has been in touch and the whole email list. A password
            on its own is one reused login away from all of it.
        </p>

        <ol class="av-2fa-steps">
            <li>Open an authenticator app — Google Authenticator, Authy, 1Password, whichever you use.</li>
            <li>Scan this square.</li>
            <li>Type the six-digit code it shows.</li>
        </ol>

        <div class="av-2fa-qr">{!! $this->qrCode() !!}</div>

        {{--
            The secret in text, for a password manager or a device that cannot scan. Same secret,
            same risk as the QR — it is shown for the same one enrolment and never again.
        --}}
        <details class="av-2fa-manual">
            <summary>Can't scan it?</summary>
            <p>Enter this key by hand: <code>{{ $secret }}</code></p>
        </details>

        <x-filament-panels::form wire:submit="confirm">
            {{ $this->form }}

            <x-filament::button type="submit" class="av-2fa-submit">
                Confirm and continue
            </x-filament::button>
        </x-filament-panels::form>
    @endif

    <x-filament-actions::modals />
</div>
