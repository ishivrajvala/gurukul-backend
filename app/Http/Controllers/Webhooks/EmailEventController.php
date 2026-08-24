<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BOUNCES AND COMPLAINTS, reported back by the mail provider.
 *
 * WHY THIS HAS TO EXIST AT ALL. A bounce cannot be detected when sending. SMTP accepts the message,
 * says 250 OK, and only afterwards discovers the mailbox does not exist — so the failure arrives
 * minutes or hours later, out of band, as a webhook. Without this endpoint every dead address stays
 * marked `sent` for ever, the list quietly rots, and the first anybody notices is when the sending
 * domain's reputation has already gone.
 *
 * PROVIDER-AGNOSTIC ON PURPOSE. SES wraps its payload in SNS, Postmark posts flat JSON, Mailgun
 * posts `event-data`. Rather than committing to one before the provider is chosen, this reads the
 * two things every provider sends — an address and an event type — from whichever shape arrived.
 * When the provider is settled, narrow this and verify its signature properly.
 *
 * THE SHARED SECRET IS THE ONLY AUTHORISATION. This route is public by necessity, and without it
 * anybody who knows the URL can unsubscribe the whole list one address at a time.
 */
class EmailEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.email_webhook.secret');

        /*
         * NO SECRET CONFIGURED MEANS THE ENDPOINT IS SHUT, not that it is open. A missing
         * environment variable must never be the thing that makes an endpoint public.
         */
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $payload = $request->all();

        $email = $this->firstString($payload, ['email', 'Email', 'recipient', 'Recipient', 'destination']);
        $event = mb_strtolower($this->firstString($payload, ['event', 'Type', 'RecordType', 'eventType', 'notificationType']) ?? '');
        $reason = $this->firstString($payload, ['reason', 'Description', 'Details', 'DiagnosticCode', 'description']);

        if ($email === null || $event === '') {
            /*
             * 200, NOT 422. A provider that gets an error retries, and then retries again, and
             * eventually disables the webhook — over a payload shape this code simply did not
             * recognise. Accepting and logging is the behaviour that keeps the real events flowing.
             */
            report(new \RuntimeException('Unrecognised email webhook payload: '.json_encode($payload)));

            return response()->json(['message' => 'Ignored.']);
        }

        $isBounce = str_contains($event, 'bounce');
        $isComplaint = str_contains($event, 'complaint') || str_contains($event, 'spam');

        if (! $isBounce && ! $isComplaint) {
            return response()->json(['message' => 'Ignored.']);
        }

        /*
         * The most recent send to this address, so the failure lands on the campaign that caused it
         * rather than floating free. There may be none — a provider can report on mail this
         * application never sent — and that is not an error.
         */
        CampaignRecipient::query()
            ->where('email', $email)
            ->latest('id')
            ->first()
            ?->forceFill([
                'status' => $isComplaint ? CampaignRecipient::STATUS_COMPLAINED : CampaignRecipient::STATUS_BOUNCED,
                'bounced_at' => now(),
                'error' => $reason,
            ])->save();

        $subscriber = Subscriber::where('email', $email)->first();

        if ($subscriber !== null) {
            /*
             * A COMPLAINT IS AN UNSUBSCRIBE, and a hard one. Somebody pressing "this is spam" has
             * asked to stop in the strongest terms available to them; treating it as a bounce would
             * file it as a technical fault and imply the address might come back.
             */
            $isComplaint
                ? $subscriber->unsubscribe()
                : $subscriber->markBounced($reason);
        }

        return response()->json(['message' => 'Recorded.']);
    }

    /** The first of these keys present in the payload, at the top level or one nest down. */
    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key)
                ?? data_get($payload, 'event-data.'.$key)
                ?? data_get($payload, 'mail.'.$key)
                ?? data_get($payload, 'bounce.'.$key);

            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (is_array($value) && isset($value[0]) && is_string($value[0])) {
                return $value[0];
            }
        }

        return null;
    }
}
