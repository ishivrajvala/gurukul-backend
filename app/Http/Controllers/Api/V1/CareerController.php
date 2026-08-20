<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Filament\Admin\Resources\JobApplicationResource;
use App\Http\Controllers\Controller;
use App\Support\NotifyAdmins;
use App\Models\JobApplication;
use App\Models\JobRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Careers: the open roles out, the applications in.
 *
 * THE ROLES FEED IS ALSO A JobPosting FEED. The consumer emits structured data from these rows, so
 * `location`, `commitment` and `datePosted` end up on job boards as fact rather than as page copy.
 * An empty list is a real answer — the careers page has a "no openings" state and says so honestly.
 */
class CareerController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = JobRole::published()->get()->map(fn (JobRole $r): array => [
            /* `id` on the consumer, where it is the anchor a card links to. Named `slug` here
               because that is what it is: a stable public identifier, not a database key. */
            'slug' => $r->slug,
            'title' => $r->title,
            'team' => $r->team,
            'location' => $r->location,
            'commitment' => $r->commitment,
            'openings' => $r->openings,
            'experience' => $r->experience,
            'datePosted' => $r->date_posted?->toDateString(),
            'summary' => $r->summary,
            'responsibilities' => $r->responsibilities ?? [],
            'looking' => $r->looking ?? [],
        ]);

        return response()->json(['data' => $roles]);
    }

    /**
     * One application, with a CV.
     *
     * MULTIPART, and the CV is REQUIRED — an application without one is not an application, and the
     * form has always enforced that client-side. The checks are repeated here because the browser's
     * are convenience: this is one of two endpoints on the site that accepts a document from a
     * stranger, and anybody can post to it directly.
     *
     * THE ROLE MUST BE OPEN. `exists` alone would accept an application to a role that was closed
     * last week, which is a candidate writing a cover letter for a job nobody is hiring for.
     */
    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'exists:job_roles,slug'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'portfolio' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:5000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $role = JobRole::where('slug', $data['role'])->first();

        if ($role === null || ! $role->is_published) {
            return response()->json([
                'message' => 'That role has closed. Please look at what else is open.',
            ], 422);
        }

        JobApplication::create([
            'job_role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'portfolio_url' => $data['portfolio'] ?? null,
            'note' => $data['note'] ?? null,
            /* The `local` disk, which no URL reaches: a CV carries a home address and a phone
               number, and the public disk is readable by anybody who guesses the filename. */
            'cv_path' => $request->file('cv')->store('applications/cvs', 'local'),
            'status' => 'pending',
        ]);

        /*
         * The one inbox whose delay costs somebody else. A candidate who waits three weeks has
         * taken another job, and nothing on a dashboard says "this arrived while you were out".
         */
        NotifyAdmins::of(
            'New application: '.$role->title,
            $data['name'].' applied. Read it before they take another job.',
            JobApplicationResource::getUrl(),
            'heroicon-o-identification',
        );

        return response()->json(['message' => 'Received.'], 201);
    }
}
