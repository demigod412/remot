<?php

namespace Database\Seeders;

use App\Models\ContentSection;
use Illuminate\Database\Seeder;

class ContentSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'section_key'  => 'hero',
                'section_data' => [
                    'badge'    => 'Earn Real Rewards',
                    'heading'  => 'Complete Micro Jobs,<br>Earn <span class="text-primary">Job Station Coins</span>',
                    'subtext'  => 'Join thousands of earners completing simple tasks and converting coins into real money.',
                    'cta_text' => 'Start Earning',
                    'cta_url'  => '/register',
                    'image'    => '',
                ],
            ],
            [
                'section_key'  => 'stats',
                'section_data' => [
                    'items' => [
                        ['label' => 'Active Workers',   'value' => '12,000+'],
                        ['label' => 'Works Completed',  'value' => '250,000+'],
                        ['label' => 'Coins Distributed','value' => '5M+'],
                        ['label' => 'Work Posters',     'value' => '800+'],
                    ],
                ],
            ],
            [
                'section_key'  => 'how_it_works',
                'section_data' => [
                    'heading' => 'How Job Station Works',
                    'subtext' => 'Three simple steps to start earning.',
                    'steps'   => [
                        ['icon' => 'user-plus',   'title' => 'Sign Up',         'text' => 'Create your free account in seconds.'],
                        ['icon' => 'briefcase',   'title' => 'Pick a Work',     'text' => 'Browse available micro jobs and pick one that suits you.'],
                        ['icon' => 'coins',       'title' => 'Earn Coins',      'text' => 'Complete the work, submit proof, and get paid in Job Station Coins.'],
                    ],
                ],
            ],
            [
                'section_key'  => 'features',
                'section_data' => [
                    'heading' => 'Why Choose Job Station?',
                    'subtext' => 'Built for earners and work posters alike.',
                    'items'   => [
                        ['icon' => 'zap',         'title' => 'Instant Tasks',       'text' => 'Find micro jobs that take minutes to complete.'],
                        ['icon' => 'shield-check', 'title' => 'Secure Payments',   'text' => 'Coins are held safely and released only on approval.'],
                        ['icon' => 'trending-up', 'title' => 'Grow Your Income',   'text' => 'Refer friends and earn bonus coins automatically.'],
                        ['icon' => 'globe',        'title' => 'Work Anywhere',      'text' => 'Fully remote — work from anywhere in the world.'],
                    ],
                ],
            ],
            [
                'section_key'  => 'cta_banner',
                'section_data' => [
                    'heading'  => 'Ready to Start Earning?',
                    'subtext'  => 'Join Job Station today and turn your spare time into real rewards.',
                    'cta_text' => 'Create Free Account',
                    'cta_url'  => '/register',
                ],
            ],
            [
                'section_key'  => 'footer',
                'section_data' => [
                    'tagline'     => 'Earn coins, cash out real money.',
                    'copyright'   => '© {{year}} Job Station. All rights reserved.',
                    'links'       => [
                        ['label' => 'Privacy Policy', 'url' => '/page/privacy-policy'],
                        ['label' => 'Terms of Use',   'url' => '/page/terms-of-use'],
                        ['label' => 'Contact Us',     'url' => '/contact'],
                    ],
                ],
            ],
        ];

        foreach ($sections as $section) {
            ContentSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                ['section_data' => $section['section_data']]
            );
        }
    }
}
