<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

/**
 * Every platform Avdhara might use, whether or not there is an account yet.
 *
 * The four live URLs are the ones that were literals in the website. Twitter/X and TikTok are seeded
 * DELIBERATELY EMPTY: the user asked for them to exist so that opening an account later is pasting
 * a link into a waiting field, and until then they are absent from the site entirely.
 *
 * `updateOrCreate` on platform, so re-running never duplicates a row and never overwrites a URL
 * somebody has since edited in the panel — only the label and the ordering are refreshed.
 */
class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['platform' => 'facebook',  'label' => 'Facebook',  'url' => 'https://www.facebook.com/avdhara.gurukul',        'sort_order' => 1],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/avdhara.gurukul/',      'sort_order' => 2],
            ['platform' => 'linkedin',  'label' => 'LinkedIn',  'url' => 'https://www.linkedin.com/company/avdhara-gurukul', 'sort_order' => 3],
            /* The handle has NO dot, unlike the other three. Confirmed by the account owner. */
            ['platform' => 'youtube',   'label' => 'YouTube',   'url' => 'https://www.youtube.com/@avdharagurukul',          'sort_order' => 4],

            /* No account yet. Empty on purpose — the site shows nothing until a URL is pasted in. */
            ['platform' => 'twitter',   'label' => 'X',         'url' => null, 'sort_order' => 5],
            ['platform' => 'tiktok',    'label' => 'TikTok',    'url' => null, 'sort_order' => 6],
        ];

        foreach ($links as $link) {
            SocialLink::updateOrCreate(
                ['platform' => $link['platform']],
                /* A URL already set in the panel wins over the seed value. */
                SocialLink::where('platform', $link['platform'])->exists()
                    ? ['label' => $link['label'], 'sort_order' => $link['sort_order']]
                    : $link,
            );
        }
    }
}
