<?php

declare(strict_types=1);

/**
 * The parent guide — the one file the site promises to email people.
 *
 * A CONFIG ENTRY RATHER THAN A HARD-CODED PATH, because the file itself is not in the repository
 * and should not be: it is a designed PDF that will be replaced without a deploy, and committing
 * each revision would put a few megabytes of binary into git history every time a sentence changes.
 *
 * `disk` is a filesystem disk name from `config/filesystems.php`. The default is `local`, so the
 * file lives at `storage/app/private/parent-guide.pdf` and is never reachable over HTTP — the guide
 * is given in exchange for an address, and a public URL would make the form pointless.
 */
return [
    'disk' => env('PARENT_GUIDE_DISK', 'local'),

    'path' => env('PARENT_GUIDE_PATH', 'parent-guide.pdf'),

    /** What the attachment is called in the recipient's mail client. */
    'filename' => env('PARENT_GUIDE_FILENAME', 'The Avdhara Parent Guide.pdf'),

    'subject' => env('PARENT_GUIDE_SUBJECT', 'Your Avdhara parent guide'),
];
