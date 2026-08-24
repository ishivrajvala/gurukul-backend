{{-- The second factor, asked once per session. Same layout as the login screen. --}}
<div class="av-auth-form">
    <img src="{{ asset('images/logo.webp') }}" alt="{{ filament()->getBrandName() }}" class="av-auth-logo" />

    <div class="av-auth-heading">
        <h1 class="av-auth-title">Two-factor</h1>
        <p class="av-auth-subtitle">Enter the code from your authenticator app.</p>
    </div>

    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament::button type="submit" class="av-2fa-submit">
            Continue
        </x-filament::button>
    </x-filament-panels::form>

    <div class="av-auth-help">
        <span class="av-auth-help-rule"></span>
        <p class="av-auth-help-text">
            Lost your phone? Use one of your recovery codes in the same box.
        </p>
    </div>

    <x-filament-actions::modals />
</div>
