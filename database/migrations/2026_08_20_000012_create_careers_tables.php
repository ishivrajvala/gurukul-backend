<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Careers: the open roles, and the applications to them.
 *
 * `job_roles`, NOT `roles`. Spatie's permission package owns `roles` in this database — the table
 * behind super_admin, admin and editor — and a vacancy called "Video Editor" landing in it would
 * either collide outright or, worse, quietly become an assignable permission role.
 *
 * TWO TABLES, and applications are NOT folded into `enquiries` with the rest.
 *
 * The rule this project already follows is that an inbox gets its own table when it carries
 * something the schema has to hold. An application holds three: the ROLE it is for (a foreign key,
 * so unpublishing a vacancy cannot orphan the people who applied to it), a CV that is REQUIRED
 * rather than optional, and a portfolio link that half these roles are actually judged on. Folding
 * those into a JSON payload column would put a hiring record somewhere untyped, and would leave
 * every application interleaved with newsletter signups in one list.
 *
 * `responsibilities` and `looking` are JSON arrays rather than child tables. They are prose bullets
 * with no identity, no ordering beyond their position in the list, and nothing else ever joins to
 * them; a table would buy referential integrity over sentences that do not need it.
 *
 * ON DELETE RESTRICT for the role. Deleting a vacancy somebody has applied to should fail loudly
 * rather than cascade away their application or silently null the one field that says what they
 * applied FOR. Close a role instead — `is_published` is what takes it off the site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_roles', function (Blueprint $table): void {
            $table->id();
            /* The public identifier and the card's anchor. The frontend links to `#<slug>`. */
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('team');
            $table->string('location');
            /* Free text rather than an enum: 'Full time', 'Part time', 'Contract' today, and a
               constraint here would need a migration the first time somebody writes 'Internship'. */
            $table->string('commitment')->default('Full time');
            $table->unsignedSmallInteger('openings')->default(1);
            $table->string('experience')->nullable();
            /* Required by JobPosting structured data, which the careers page emits. */
            $table->date('date_posted');
            $table->text('summary');
            $table->json('responsibilities')->nullable();
            $table->json('looking')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });

        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_role_id')->constrained('job_roles')->restrictOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->text('note')->nullable();
            /*
             * Not nullable, and not on the public disk. A CV is the whole point of an application,
             * and it carries somebody's home address and phone number — the `local` disk has no URL
             * pointing at it, so the admin download action is the only way in.
             */
            $table->string('cv_path');
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_roles');
    }
};
