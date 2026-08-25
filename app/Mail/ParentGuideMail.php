<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

/**
 * The parent guide, sent to one person who asked for it.
 *
 * NOT QUEUED ITSELF — `SendParentGuide` is the queued job, and it wraps the send so it can record
 * the outcome on the lead. Queueing the mailable too would put the delivery on a second hop where
 * nothing is watching, and the lead would read `new` for ever while the guide had in fact gone.
 *
 * NO UNSUBSCRIBE HEADER, and that is not an oversight. This is a transactional message — one person
 * asked for one file and this is the file — not bulk mail. Asking for a guide does not put anybody
 * on the newsletter; that is a separate consent with its own form, and conflating them is exactly
 * how a list stops being one people agreed to.
 */
class ParentGuideMail extends Mailable
{
    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) config('guide.subject'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.parent-guide',
            with: ['name' => $this->lead->name],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk((string) config('guide.disk'), (string) config('guide.path'))
                ->as((string) config('guide.filename'))
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Whether the file this mail exists to carry is actually there.
     *
     * THE EMPTY-PATH GUARD IS NOT DEFENSIVE PADDING. `Storage::exists('')` is true — the disk root
     * exists — so a missing or unreadable config silently passes this check, and the failure then
     * surfaces from inside Symfony as `body ... must be a string ... (got "null")`, which names
     * nothing and points nowhere near the cause. That is not hypothetical: it happened the first
     * time this ran, because `queue:work` had booted before `config/guide.php` was added and was
     * still holding a config that had no `guide` key in it at all. See RUNBOOK.md — a running
     * worker does not see your changes.
     */
    public static function fileExists(): bool
    {
        $disk = (string) config('guide.disk');
        $path = (string) config('guide.path');

        if ($disk === '' || $path === '') {
            return false;
        }

        return Storage::disk($disk)->exists($path);
    }
}
