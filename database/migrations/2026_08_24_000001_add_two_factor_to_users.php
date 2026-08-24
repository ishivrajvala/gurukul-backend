<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MANDATORY TWO-FACTOR FOR EVERY PANEL ACCOUNT.
 *
 * The admin is the highest-value target on this system: it holds every family who has been in
 * touch, the whole email list, and the ability to publish anything to the public site. A password
 * is one reused string away from all of it, and credential stuffing does not need to be clever.
 *
 * ── WHY THE COLUMNS LOOK LIKE THIS ────────────────────────────────────────────────────────
 *
 * `two_factor_secret` and `two_factor_recovery_codes` are ENCRYPTED at the model, not hashed. A
 * TOTP secret has to be readable to verify a code against it — hashing it would make it useless —
 * so the protection is encryption with the app key rather than a one-way digest. That is also why
 * a leaked database dump alone is not enough: it needs `APP_KEY` too, which is why that key belongs
 * in a secrets manager and never in the repository.
 *
 * `two_factor_confirmed_at` IS THE ENROLMENT GATE and is deliberately separate from having a
 * secret. Generating a secret is not proof anybody scanned it; confirming means they typed a code
 * their authenticator produced. Without this distinction somebody could be locked out by a secret
 * that was created and never added to a phone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->after('password');

            /*
             * RECOVERY CODES ARE NOT OPTIONAL WHEN MFA IS MANDATORY. A phone is lost, reset or
             * replaced eventually, and without a second way in that is a permanently locked
             * account — which in practice means somebody turns the whole feature off in a hurry.
             * Eight single-use codes, shown once at enrolment.
             */
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
