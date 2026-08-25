# Article copy

The written body of every Parent Journal article, as `{ "<slug>": "<html>" }`, one file per batch.

Load a file with:

```bash
php artisan journal:import-bodies database/data/bodies/parenting-a.json
```

Add `--overwrite` to replace copy that is already in the database. Without it the command refuses,
because the panel is where article copy is edited and a file should not silently replace a week of
somebody's work.

## Why these are in the repository at all

**Because otherwise this writing exists in one Postgres database and nowhere else.** The index —
titles, standfirst, filing, ordering — is generated from the frontend's models and can be rebuilt at
any time. The bodies cannot: they are the actual work, they took a long time, and until they were
committed here a dropped database would have lost them with no way back.

They are a SOURCE, not the source of truth. Once copy is live, the panel is where it is edited, and
these files will fall behind the moment somebody fixes a typo in the admin. That is fine and
intended: they exist so the archive can be reconstructed, not so it can be round-tripped.

`JournalSeeder` never touches `content`, so reseeding the index cannot disturb any of this.

## House style, briefly

Editor HTML, matching what the Tiptap editor in the panel produces, and rendered through the one
component that sanitises it.

- **No em dashes.** Comma, colon, semicolon or full stop instead, chosen by the job the dash was
  doing. The em dash is the strongest surface tell of machine-written text and the whole archive was
  cleared of it. The EN dash in age ranges (`2–4`) is a different character and stays.
- **Every article is structured differently.** No shared template: some open with a blunt answer,
  some are a numbered sequence, some a comparison table, some age-by-age, some plain prose with two
  headings. Read together, twenty identical shapes are obviously generated, which is the one thing
  this archive cannot afford.
- **Titles are the question a parent types.** The developmental position goes inside the piece, not
  in the H1.
- A `<small>` clinical note only where the subject is health, safety or development, never as a
  reflexive hedge.
