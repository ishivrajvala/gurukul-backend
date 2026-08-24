<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /**
     * Who may open the admin panel.
     *
     * WITHOUT THIS, NOBODY CAN — outside `local`. Filament only waves a user through when the app
     * is in the local environment unless the model says otherwise, so a deployed panel answered
     * every request with a flat 403 and nothing explaining it. It was invisible here because
     * development IS local; `AdminPagesRenderTest` found it by running as `testing`.
     *
     * The check is a role, not an email domain or a boolean column: roles are already installed
     * (spatie/laravel-permission) and already assigned, and an email-suffix check is the kind of
     * rule that quietly grants access to anybody who signs up with the right address.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        /*
         * EVERY ROLE THAT EXISTS, not a list written from memory.
         *
         * This was `['super_admin', 'admin', 'editor']` while the database actually held
         * `super_admin` and `content_strategist`. Two of the three names matched nothing, and the
         * one real role that was missing meant a content strategist signing in got a flat 403 with
         * nothing explaining it — the panel simply refused the person it was built for.
         *
         * Any role is a panel role here. Filament is the only thing this application authenticates
         * anybody for, so a user with a role and no way in is a bug rather than a policy; what each
         * role may DO is a permissions question, and belongs on the resources.
         */
        return $this->roles()->exists();
    }

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use \App\Models\Concerns\HasTwoFactor;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        /* Never serialised, never logged — see HasTwoFactor. */
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
