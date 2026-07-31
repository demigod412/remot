<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\WorkCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        // Skills grouped by the work category they belong to, so suggestions
        // can match a user's skills to works in the same category.
        $byCategory = [
            'Social Media' => [
                'Instagram Marketing', 'Facebook Marketing', 'TikTok Growth',
                'Twitter/X Engagement', 'YouTube Promotion', 'Community Management',
            ],
            'App & Play Store' => [
                'App Testing', 'App Reviews', 'Mobile QA', 'Beta Testing',
            ],
            'Website & Traffic' => [
                'SEO', 'Web Traffic Generation', 'Link Building', 'Form Filling',
            ],
            'Content Creation' => [
                'Copywriting', 'Blog Writing', 'Video Editing', 'Graphic Design', 'Product Reviews',
            ],
            'Survey & Research' => [
                'Market Research', 'Survey Completion', 'Data Entry',
            ],
            'Referral & Invite' => [
                'Referral Marketing', 'Lead Generation', 'Networking',
            ],
        ];

        foreach ($byCategory as $categoryName => $skills) {
            $category = WorkCategory::where('name', $categoryName)->first();

            foreach ($skills as $name) {
                Skill::updateOrCreate(
                    ['name' => $name],
                    [
                        'slug'        => Str::slug($name),
                        'category_id' => $category?->id,
                        'status'      => true,
                    ]
                );
            }
        }
    }
}
