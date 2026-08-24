<?php

declare(strict_types=1);

namespace App\Filament\Admin\Auth;

use App\Http\Middleware\RequireTwoFactor;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use PragmaRX\Google2FA\Google2FA;

/**
 * Setting up the second factor. Nobody reaches the panel without passing through here once.
 *
 * ── THE SECRET IS CREATED ON MOUNT, CONFIRMED ONLY BY A CODE ──────────────────────────────
 *
 * Scanning a QR is not proof of anything — the phone might not have saved it, the clock might be
 * wrong, the app might have been closed. Confirmation requires a code the person's own
 * authenticator produced, which is the only evidence that the thing they will need tomorrow
 * actually works. Until then the account stays unenrolled and lands back here.
 *
 * ── THE RECOVERY CODES ARE SHOWN ONCE ─────────────────────────────────────────────────────
 *
 * They are hashed, so this is genuinely the only time they can be displayed — not a policy but a
 * fact of the storage. With MFA mandatory they are the only way back in after a lost phone, short
 * of `php artisan admin:reset-2fa`, which needs server access.
 */
class TwoFactorSetup extends Page
{
    protected static string $view = 'filament.admin.auth.two-factor-setup';

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

    public string $secret = '';

    /** @var array<int, string> Shown once, after confirming. */
    public array $recoveryCodes = [];

    public bool $confirmed = false;

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if ($user->twoFactorEnabled()) {
            /* Already done. Nothing here to do again — sending them back avoids re-enrolment. */
            redirect()->to(Filament::getUrl());

            return;
        }

        /*
         * A FRESH SECRET EACH TIME THIS PAGE IS OPENED, on purpose. An abandoned enrolment leaves a
         * secret behind; reusing it would mean a QR photographed weeks ago on a shared screen is
         * still valid. Nothing is confirmed yet, so nothing is lost by replacing it.
         */
        $this->secret = $user->startTwoFactorEnrolment();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Six-digit code')
                    ->required()
                    ->autofocus()
                    ->autocomplete('one-time-code')
                    /* Exactly six digits: a length rule catches a mistyped code before a failed verify. */
                    ->rule('digits:6')
                    ->placeholder('123456')
                    ->helperText('From your authenticator app, after scanning the square above.'),
            ])
            ->statePath('data');
    }

    public function confirm(): void
    {
        $user = Filament::auth()->user();
        $code = (string) ($this->form->getState()['code'] ?? '');

        if (! $user->verifyTwoFactorCode($code)) {
            Notification::make()
                ->title('That code did not match')
                ->body('Codes change every 30 seconds — wait for the next one and try again. If it keeps failing, the phone\'s clock may be out of step.')
                ->danger()
                ->send();

            return;
        }

        $user->confirmTwoFactor();
        $this->recoveryCodes = $user->generateRecoveryCodes();
        $this->confirmed = true;

        /* Enrolling proves possession, so this session counts as verified — no second challenge. */
        session([RequireTwoFactor::VERIFIED => true]);
    }

    /** Off to the panel, once the codes have been acknowledged. */
    public function finish(): void
    {
        redirect()->to(Filament::getUrl());
    }

    /**
     * The QR, as an inline SVG.
     *
     * SVG RATHER THAN A PNG DATA URI because it needs no image extension on the server and stays
     * sharp at any size. It is generated here and never stored: the QR is only a rendering of the
     * `otpauth://` URI, and writing it to disk would leave the secret sitting in a file.
     */
    public function qrCode(): Htmlable
    {
        $uri = (new Google2FA())->getQRCodeUrl(
            config('app.name'),
            (string) Filament::auth()->user()->email,
            $this->secret,
        );

        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 0), new SvgImageBackEnd())))->writeString($uri);

        return str($svg)->toHtmlString();
    }

    public function getTitle(): string
    {
        return 'Set up two-factor';
    }

    public function getHeading(): string
    {
        return 'Set up two-factor';
    }
}
