import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

/**
 * The Avdhara admin theme's Tailwind config.
 *
 * BUILT WITH THE TAILWIND v3 CLI, ON PURPOSE, and never through this project's Vite. Filament 3
 * ships a Tailwind v3 preset; the application's own `app.css` is on Tailwind v4, and the two majors
 * cannot share a build. Compiling this one file with `npx tailwindcss@3` keeps v3 out of the
 * project's dependencies entirely — it is fetched for the length of the command and gone after —
 * while `app.css` carries on through `@tailwindcss/vite` untouched.
 *
 *   npm run theme          (see package.json)
 *
 * That is also why the panel registers `->theme(asset('css/filament/admin/theme.css'))` rather than
 * `->viteTheme(...)`: the output is a plain built file in `public/`, with no Vite manifest entry.
 *
 * THE TOKENS BELOW ARE THE SITE'S, copied from the frontend's `models/tokens.ts` rather than
 * re-picked. Six brand colours and two faces; if this file and that one ever disagree, that one
 * wins.
 */
export default {
    presets: [preset],
    content: [
        './app/Filament/Admin/**/*.php',
        './resources/views/filament/admin/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                /*
                 * BALOO 2 IS THE PANEL'S FACE. Everything wears it: headings, labels, table cells,
                 * buttons, numbers, navigation. Nunito Sans is kept for ONE job — description and
                 * helper text, the sentences a person reads rather than scans.
                 *
                 * THIS IS A DELIBERATE REVERSAL, and it is locked. The panel previously split them
                 * the way the website does: Baloo for display, Nunito for anything read row by row,
                 * on the reasoning that Baloo is warm at 32px and mushy at 13px. That reasoning is
                 * real — a Baloo table does scan slightly slower than a Nunito one — and it was
                 * overruled on purpose, because a panel that shares the brand's face everywhere
                 * reads as part of Avdhara rather than as a tool bolted to the side of it. The
                 * mushiness is answered by SIZE instead: nothing in this panel is 13px any more
                 * (see the TEXT SIZE block in theme.css), which is what makes the face hold up.
                 *
                 * Do not split them again without changing that block too.
                 */
                display: ['Baloo 2', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                sans: ['Baloo 2', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                /* The one exception: prose. Applied in theme.css, not by utility class. */
                body: ['Nunito Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                /* The six. Not a palette to extend — see the frontend's models/tokens.ts. */
                avdhara: {
                    indigo: '#27156B',
                    marigold: '#F7B75F',
                    green: '#56A195',
                    orange: '#F0713D',
                    ink: '#282828',
                    /* A derived opacity of indigo, never a seventh colour. The site's `line`. */
                    line: 'rgba(39, 21, 107, 0.12)',
                },
            },
            borderRadius: {
                /* SCALE.cardRadius on the site. Every panel, card and table shell uses it. */
                card: '20px',
                control: '12px',
            },
            boxShadow: {
                /* The site's card shadow, verbatim. Soft and indigo-tinted, never neutral grey. */
                card: '0 1px 2px rgba(39, 21, 107, 0.04), 0 10px 24px -14px rgba(39, 21, 107, 0.14)',
            },
        },
    },
}
