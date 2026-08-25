<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Filament\Admin\Resources\CircleQuestionResource;
use App\Filament\Admin\Resources\CircleSignupResource;
use App\Filament\Admin\Resources\LeadResource;
use App\Filament\Admin\Resources\StorySubmissionResource;
use App\Http\Controllers\Controller;
use App\Support\NotifyAdmins;
use App\Models\AgeStage;
use App\Models\Circle;
use App\Models\CircleQuestion;
use App\Models\CircleSignup;
use App\Jobs\SendParentGuide;
use App\Models\Lead;
use App\Models\LeadKind;
use App\Models\Subscriber;
use App\Models\Gathering;
use App\Models\StorySubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Everything the site sends IN.
 *
 * These endpoints are the other end of forms that have been fully built and going nowhere:
 * `viewmodels/useForm.ts` on the frontend flips `isSent` and discards the input. Until now a parent
 * who said yes reached nobody.
 *
 * NOTHING AUTO-ADMITS AND NOTHING AUTO-PUBLISHES. Every record lands as `pending` and a person
 * moves it, because that is exactly what the confirmation copy already promises: a story is
 * reviewed before publication, a question is moderated before it appears, a WhatsApp invite is sent
 * by hand. An endpoint that quietly published would make the site's own words untrue.
 *
 * VALIDATION IS FORGIVING WHERE A HUMAN READS IT NEXT. The WhatsApp rule strips spaces, brackets
 * and a leading + or 00 and then asks only for 8-15 digits: numbering plans differ by country and
 * change, and a parent whose valid number is refused has no way to argue with a form. Somebody
 * reads every one of these before anybody joins a group, so the job is catching a real slip.
 *
 * Rate limiting is on the ROUTE, not here — see routes/api.php.
 */
class SubmissionController extends Controller
{
    /** Digits only, once the punctuation people actually type is stripped. */
    private const WHATSAPP_RULE = ['required', 'string', 'max:32'];

    /** Joining a Circle, or reserving a place at one gathering. */
    public function circleSignup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:join,reserve'],
            'circleSlug' => ['nullable', 'string'],
            'gatheringSlug' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'whatsapp' => self::WHATSAPP_RULE,
            'ageStage' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->isPlausibleWhatsApp($data['whatsapp'])) {
            return response()->json([
                'message' => 'Please check the WhatsApp number, including the country code.',
                'errors' => ['whatsapp' => ['That does not look like a WhatsApp number.']],
            ], 422);
        }

        CircleSignup::create([
            'kind' => $data['kind'],
            'circle_id' => $this->circleId($data['circleSlug'] ?? null),
            'gathering_id' => $this->gatheringId($data['gatheringSlug'] ?? null),
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'age_stage_id' => $this->ageStageId($data['ageStage'] ?? null),
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);


        /*
         * The WhatsApp invite is sent by hand, which is exactly why this notification exists: the
         * parent has been told somebody will be in touch, and nothing else would say so.
         */
        NotifyAdmins::of(
            $data['kind'] === 'join' ? 'Somebody asked to join a Circle' : 'A place was reserved',
            $data['name'].' is waiting for a WhatsApp invite.',
            CircleSignupResource::getUrl(),
            'heroicon-o-user-group',
        );

        return response()->json(['message' => 'Received.'], 201);
    }

    /**
     * Ask the Circle.
     *
     * `isAnonymous` means anonymous TO OTHER PARENTS. The moderator still sees the row, which is
     * what keeps the space held rather than unattended — and the hint under the control on the site
     * says so, so nobody assumes more privacy than they have.
     */
    public function circleQuestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'circleSlug' => ['nullable', 'string'],
            /* Which surface asked. The Journal has an ask box too, and it is the same kind of
               thing — a moderated question with no reply promised — so it shares this queue. */
            'source' => ['nullable', 'in:circle,journal'],
            'question' => ['required', 'string', 'min:10', 'max:2000'],
            'isAnonymous' => ['boolean'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        CircleQuestion::create([
            'circle_id' => $this->circleId($data['circleSlug'] ?? null),
            'source' => $data['source'] ?? 'circle',
            'question' => $data['question'],
            'is_anonymous' => (bool) ($data['isAnonymous'] ?? false),
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => 'pending',
        ]);


        NotifyAdmins::of(
            'A parent asked a question',
            'From the '.($data['source'] ?? 'circle').'. No reply was promised, but somebody should read it.',
            CircleQuestionResource::getUrl(),
            'heroicon-o-question-mark-circle',
        );

        return response()->json(['message' => 'Received.'], 201);
    }

    /**
     * Share your story.
     *
     * CONSENT IS REQUIRED AND ITS WORDING IS STORED. Avdhara's framework requires explicit family
     * consent for identifiable child stories and images, and a consent you cannot reproduce is not
     * one you can rely on months later when the wording has changed.
     *
     * A video is UPLOADED, not linked: asking for a YouTube URL asks a parent to publish their
     * child's video publicly before deciding whether to share it with us at all.
     */
    public function storySubmission(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'ageStage' => ['nullable', 'string'],
            'story' => ['nullable', 'string', 'max:5000'],
            'consent' => ['accepted'],
            'consentText' => ['required', 'string', 'max:1000'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:204800'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        /* A video OR a written story with a photo. The same rule the form enforces: a video is a
           whole story on its own, and without one a card is an empty panel in a grid of faces. */
        $hasVideo = $request->hasFile('video');
        if (! $hasVideo && (! $request->hasFile('photo') || blank($data['story'] ?? null))) {
            return response()->json([
                'message' => 'Please add a video, or a photo and your story.',
            ], 422);
        }

        StorySubmission::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'age_stage_id' => $this->ageStageId($data['ageStage'] ?? null),
            'story' => $data['story'] ?? null,
            'video_path' => $hasVideo ? $request->file('video')->store('submissions/videos', 'public') : null,
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('submissions/photos', 'public') : null,
            'has_consent' => true,
            'consent_text' => $data['consentText'],
            'status' => 'pending',
        ]);


        NotifyAdmins::of(
            'A family shared their story',
            $data['name'].' sent a story submission. Nothing publishes until somebody reads it.',
            StorySubmissionResource::getUrl(),
            'heroicon-o-heart',
        );

        return response()->json(['message' => 'Received.'], 201);
    }

    /** Waitlist, contact, newsletter, booking, parent guide, careers. */
    /**
     * The four forms where somebody is waiting on a reply.
     *
     * NEWSLETTER IS NO LONGER ONE OF THEM. It has its own endpoint below, because a subscriber is
     * not a lead: nobody handles one, and it is on a weekly clock rather than a reply-once clock.
     * The kinds accepted here come from `LeadKind` rather than a list written out again, so adding
     * a fifth form is one enum case and not three places to remember.
     */
    public function lead(Request $request): JsonResponse
    {
        $data = $request->validate([
            /* No `career`: applications go to /v1/job-applications, which files them against a
               role and requires the CV that makes an application an application. */
            'kind' => ['required', Rule::in(array_column(LeadKind::cases(), 'value'))],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'ageStage' => ['nullable', 'string'],
            'message' => ['nullable', 'string', 'max:5000'],
            'payload' => ['nullable', 'array'],
            /*
             * ATTRIBUTION, sent by the frontend with every form. All optional and all bounded: this
             * is an unauthenticated endpoint and these values come from a query string anybody can
             * write, so they are length-capped here rather than trusted. They are recorded, never
             * executed, and never used to look anything up.
             */
            'attribution' => ['nullable', 'array'],
            'attribution.utm_source' => ['nullable', 'string', 'max:80'],
            'attribution.utm_medium' => ['nullable', 'string', 'max:80'],
            'attribution.utm_campaign' => ['nullable', 'string', 'max:120'],
            'attribution.utm_content' => ['nullable', 'string', 'max:120'],
            'attribution.utm_term' => ['nullable', 'string', 'max:120'],
            'attribution.first_source' => ['nullable', 'string', 'max:80'],
            'attribution.first_campaign' => ['nullable', 'string', 'max:120'],
            'attribution.landing_path' => ['nullable', 'string', 'max:200'],
            'attribution.submitted_path' => ['nullable', 'string', 'max:200'],
            'attribution.referrer' => ['nullable', 'string', 'max:200'],
            /*
             * The ad click. `click_platform` is an enum in practice, but validated only as a short
             * string: a new platform must not make a real lead bounce, and the value is recorded
             * rather than dispatched on.
             */
            'attribution.click_id' => ['nullable', 'string', 'max:200'],
            'attribution.click_platform' => ['nullable', 'string', 'max:20'],
            'attribution.first_click_id' => ['nullable', 'string', 'max:200'],
        ]);

        $kind = LeadKind::from($data['kind']);

        $lead = Lead::create([
            'kind' => $kind->value,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'age_stage_id' => $this->ageStageId($data['ageStage'] ?? null),
            'message' => $data['message'] ?? null,
            'payload' => $data['payload'] ?? null,
            /* Where this kind's process starts, which the model layer decides. */
            'status' => $kind->initialStatus(),
        ] + $this->attribution($data));

        /*
         * THE GUIDE SENDS ITSELF.
         *
         * Somebody typed an address to get one file. Making them wait on a person to notice the row
         * and forward it is the difference between a working promise and a form that looks broken —
         * and it is why `new → sent` on this kind used to describe a task nobody was doing.
         *
         * QUEUED, so the parent's request returns immediately whatever the mail server is doing, and
         * so a slow or refused handshake cannot turn a successful submission into a 500. The job
         * writes the outcome back onto this lead, including the reason when it fails. It needs
         * `queue:work` running — see RUNBOOK.md, where that is already not optional.
         */
        if ($kind === LeadKind::ParentGuide) {
            SendParentGuide::dispatch($lead);
        }

        /* The row is saved; this is a courtesy on top of it and never throws. */
        NotifyAdmins::of(
            'New '.lcfirst($kind->label()).' lead',
            ($data['name'] ?? $data['email']).' used the '.lcfirst($kind->label()).' form.',
            LeadResource::getUrl(),
        );

        return response()->json(['message' => 'Received.'], 201);
    }

    /**
     * The email list, which is deliberately not the lead inbox.
     *
     * ALWAYS 201, EVEN FOR AN ADDRESS ALREADY ON THE LIST. `Subscriber::subscribe` upserts, so a
     * second signup updates the row rather than failing a unique constraint — and the response
     * must not differ, because a different answer for "already subscribed" turns this open endpoint
     * into a way to test whether a given person is on the list.
     *
     * NO ADMIN NOTIFICATION. Every lead raises one because somebody has to act; a subscriber needs
     * nobody, and a bell that rings for each signup is a bell people learn to ignore — which would
     * cost the notifications that do matter.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:60'],
            /*
             * ATTRIBUTION, sent by the frontend with every form. All optional and all bounded: this
             * is an unauthenticated endpoint and these values come from a query string anybody can
             * write, so they are length-capped here rather than trusted. They are recorded, never
             * executed, and never used to look anything up.
             */
            'attribution' => ['nullable', 'array'],
            'attribution.utm_source' => ['nullable', 'string', 'max:80'],
            'attribution.utm_medium' => ['nullable', 'string', 'max:80'],
            'attribution.utm_campaign' => ['nullable', 'string', 'max:120'],
            'attribution.utm_content' => ['nullable', 'string', 'max:120'],
            'attribution.utm_term' => ['nullable', 'string', 'max:120'],
            'attribution.first_source' => ['nullable', 'string', 'max:80'],
            'attribution.first_campaign' => ['nullable', 'string', 'max:120'],
            'attribution.landing_path' => ['nullable', 'string', 'max:200'],
            'attribution.submitted_path' => ['nullable', 'string', 'max:200'],
            'attribution.referrer' => ['nullable', 'string', 'max:200'],
            /*
             * The ad click. `click_platform` is an enum in practice, but validated only as a short
             * string: a new platform must not make a real lead bounce, and the value is recorded
             * rather than dispatched on.
             */
            'attribution.click_id' => ['nullable', 'string', 'max:200'],
            'attribution.click_platform' => ['nullable', 'string', 'max:20'],
            'attribution.first_click_id' => ['nullable', 'string', 'max:200'],
        ]);

        $subscriber = Subscriber::subscribe($data['email'], $data['name'] ?? null, $data['source'] ?? null);

        /*
         * ATTRIBUTION IS ONLY WRITTEN ONCE — `filled()` guards each field, so a subscriber who
         * signs up again from a different page keeps the campaign that first brought them in.
         * Overwriting it would mean the newest touch always wins and the list slowly reattributes
         * itself to whatever ran most recently.
         */
        $attribution = array_filter($this->attribution($data), fn ($v): bool => filled($v));

        foreach (['utm_source', 'utm_campaign', 'landing_path'] as $field) {
            if (filled($attribution[$field] ?? null) && blank($subscriber->{$field})) {
                $subscriber->{$field} = $attribution[$field];
            }
        }

        $subscriber->save();

        return response()->json(['message' => 'Subscribed.'], 201);
    }

    /**
     * Deliberately forgiving. Strips the punctuation people actually type, then asks only whether
     * 8 to 15 digits remain — 15 is the E.164 maximum and nothing shorter than 8 is a mobile number
     * anywhere Avdhara operates.
     */
    private function isPlausibleWhatsApp(string $value): bool
    {
        $digits = preg_replace('/[\s()\-.]/', '', $value);
        $digits = preg_replace('/^(\+|00)/', '', (string) $digits);

        return (bool) preg_match('/^\d{8,15}$/', (string) $digits);
    }

    private function circleId(?string $slug): ?int
    {
        return $slug ? Circle::where('slug', $slug)->value('id') : null;
    }

    private function gatheringId(?string $slug): ?int
    {
        return $slug ? Gathering::where('slug', $slug)->value('id') : null;
    }

    /**
     * The attribution block, flattened onto the columns.
     *
     * ONE MAPPER FOR BOTH ENDPOINTS, so a lead and a subscriber can never disagree about what
     * `utm_source` means. Missing keys become null rather than absent, which is what keeps a
     * partial payload from leaving a column at whatever it was before.
     *
     * @return array<string, string|null>
     */
    private function attribution(array $data): array
    {
        $a = $data['attribution'] ?? [];

        return [
            'utm_source' => $a['utm_source'] ?? null,
            'utm_medium' => $a['utm_medium'] ?? null,
            'utm_campaign' => $a['utm_campaign'] ?? null,
            'utm_content' => $a['utm_content'] ?? null,
            'utm_term' => $a['utm_term'] ?? null,
            'first_source' => $a['first_source'] ?? null,
            'first_campaign' => $a['first_campaign'] ?? null,
            'landing_path' => $a['landing_path'] ?? null,
            'submitted_path' => $a['submitted_path'] ?? null,
            'referrer' => $a['referrer'] ?? null,
            'click_id' => $a['click_id'] ?? null,
            'click_platform' => $a['click_platform'] ?? null,
            'first_click_id' => $a['first_click_id'] ?? null,
        ];
    }

    private function ageStageId(?string $key): ?int
    {
        return $key ? AgeStage::where('key', $key)->value('id') : null;
    }
}
