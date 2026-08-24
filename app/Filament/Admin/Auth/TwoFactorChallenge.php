<?php

declare(strict_types=1);

namespace App\Filament\Admin\Auth;

use App\Http\Middleware\RequireTwoFactor;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * The second factor, asked once per session.
 *
 * ── RATE LIMITED, AND THAT IS THE WHOLE POINT OF A SIX-DIGIT CODE ─────────────────────────
 *
 * A TOTP code is one of a million, which sounds like a lot and is not: unthrottled, a script gets
 * through in minutes. Five attempts a minute turns that into years. This is the single most
 * important line on the page — a second factor without a rate limit is barely a second factor.
 *
 * ── RECOVERY CODES ARE ACCEPTED HERE TOO ──────────────────────────────────────────────────
 *
 * One field takes both. Somebody who has lost their phone is already having a bad day and should
 * not have to find a different page; the format tells the two apart without asking.
 */
class TwoFactorChallenge extends Page
{
    use WithRateLimiting;

    protected static string $view = 'filament.admin.auth.two-factor-challenge';

    protected static bool $shouldRegisterNavigation = false;

    /*
     * `Page`, NOT `SimplePage`, and the difference is routing. `SimplePage` has no
     * `registerRoutes`, so a panel asked to register one dies at boot with
     * `Method ...::registerRoutes does not exist` — before any page renders, which makes it look
     * like the whole application is broken rather than one class being the wrong base.
     *
     * The panel chrome that `Page` would normally bring is replaced by the split auth layout
     * below, so this still reads as the same door as the login screen.
     */
    protected static string $layout = 'filament.admin.auth.layout';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();

        /* Not enrolled: setup comes first. Verified already: nothing to ask. */
        if (! $user->twoFactorEnabled()) {
            redirect()->to(TwoFactorSetup::getUrl());

            return;
        }

        if (session(RequireTwoFactor::VERIFIED) === true) {
            redirect()->to(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->autofocus()
                    ->autocomplete('one-time-code')
                    ->placeholder('123456')
                    ->helperText('The six-digit code from your authenticator app, or one of your recovery codes.'),
            ])
            ->statePath('data');
    }

    public function verify(): void
    {
        try {
            /* Five a minute. See the note on this class. */
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Too many attempts')
                ->body("Wait {$exception->secondsUntilAvailable} seconds and try again.")
                ->danger()
                ->send();

            return;
        }

        $user = Filament::auth()->user();
        $code = trim((string) ($this->form->getState()['code'] ?? ''));

        /*
         * A RECOVERY CODE IS TRIED ONLY WHEN THE INPUT IS NOT SIX DIGITS. Trying both against every
         * input would let an attacker guessing TOTP codes also chip away at the recovery codes with
         * the same attempts.
         */
        $ok = preg_match('/^\d{6}$/', $code) === 1
            ? $user->verifyTwoFactorCode($code)
            : $user->useRecoveryCode($code);

        if (! $ok) {
            Notification::make()
                ->title('That code did not work')
                ->body('Codes change every 30 seconds. A recovery code can only be used once.')
                ->danger()
                ->send();

            return;
        }

        /*
         * SESSION REGENERATED ON SUCCESS. The first factor already regenerated once at login; doing
         * it again here means a session id captured between the password and the second factor is
         * useless — that window is exactly what a session-fixation attack aims at.
         */
        session()->regenerate();
        session([RequireTwoFactor::VERIFIED => true]);

        if ($user->twoFactorEnabled() && $user->recoveryCodesRemaining() <= 2) {
            Notification::make()
                ->title('Running low on recovery codes')
                ->body($user->recoveryCodesRemaining().' left. Ask an administrator to reset two-factor to get a new set.')
                ->warning()
                ->persistent()
                ->send();
        }

        redirect()->intended(Filament::getUrl());
    }

    public function getTitle(): string
    {
        return 'Two-factor';
    }

    public function getHeading(): string
    {
        return 'Two-factor';
    }
}
