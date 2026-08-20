<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The pages the website itself serves.
 *
 * ONE LIST, TWO JOBS, and they pull in opposite directions — which is exactly why it must not be
 * written out twice:
 *
 *   · A landing page may NOT take one of these slugs. The frontend resolves a static route before
 *     a dynamic one, so a landing page at `/contact` would be shadowed for ever with nothing
 *     saying why the page an editor published never appears.
 *   · An announcement's CTA MAY point at one of these, and the picker offers exactly them, so
 *     nobody has to type a path and nobody mistypes one.
 *
 * Two copies of this list would drift into a state where a slug is bookable AND offered as a
 * destination, which is a link to a page that cannot exist.
 *
 * KEEP IT IN STEP WITH THE SITE'S `app/` DIRECTORY. A route missing here is a landing-page slug
 * somebody can book and then wonder about; a route wrongly here is only a name refused, which is
 * the safer of the two failures.
 */
final class SiteRoutes
{
    /** Path => the label a person would recognise it by, in the order the nav uses. */
    public const PAGES = [
        '/' => 'Home',
        '/begin' => 'Begin',
        '/founding-families' => 'Founding Families',
        '/development-pathways' => 'Development Pathways',
        '/seekers' => 'Seekers (2 to 4)',
        '/explorers' => 'Explorers (4 to 6)',
        '/builders' => 'Builders (6 to 8)',
        '/thinkers' => 'Thinkers (8 to 11)',
        '/leaders' => 'Leaders (11 to 14)',
        '/visionaries' => 'Visionaries (14 to 16)',
        '/mandala-method' => 'The Mandala Method',
        '/9-petals-framework' => 'The Nine Petals',
        '/science-behind-learning' => 'The Science Behind Learning',
        '/ancient-wisdom-modern-science' => 'Ancient Wisdom, Modern Science',
        '/our-philosophy' => 'Our Philosophy',
        '/our-story' => 'Our Story',
        '/why-avdhara' => 'Why Avdhara',
        '/parent-journal' => 'Parent Journal',
        '/parent-circles' => 'Parent Circles',
        '/parent-stories' => 'Parent Stories',
        '/careers' => 'Careers',
        '/contact' => 'Contact',
        '/privacy-policy' => 'Privacy Policy',
        '/terms-of-use' => 'Terms of Use',
        '/child-safety-policy' => 'Child Safety Policy',
    ];

    /**
     * The slugs a landing page may not take.
     *
     * Derived from `PAGES` so the two can never disagree, plus the files Next serves that are not
     * pages at all and would be shadowed just as invisibly.
     */
    public static function reservedSlugs(): array
    {
        $slugs = array_map(
            static fn (string $path): string => ltrim($path, '/'),
            array_keys(self::PAGES),
        );

        return array_values(array_filter(array_merge($slugs, ['sitemap.xml', 'robots.txt'])));
    }
}
