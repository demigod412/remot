<?php

namespace Database\Seeders;

use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use Illuminate\Database\Seeder;

class WorkCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'   => 'Social Media',
                'icon'   => 'share-2',
                'status' => 1,
                'subs'   => [
                    'Follow Account', 'Like Post', 'Comment on Post',
                    'Share Post', 'Subscribe Channel', 'View Video',
                ],
            ],
            [
                'name'   => 'App & Play Store',
                'icon'   => 'smartphone',
                'status' => 1,
                'subs'   => [
                    'Install App', 'Rate & Review App', 'Sign Up via App',
                    'In-App Action',
                ],
            ],
            [
                'name'   => 'Website & Traffic',
                'icon'   => 'globe',
                'status' => 1,
                'subs'   => [
                    'Visit Website', 'Sign Up on Website',
                    'Fill Form', 'Watch Video Ad',
                ],
            ],
            [
                'name'   => 'Content Creation',
                'icon'   => 'pen-tool',
                'status' => 1,
                'subs'   => [
                    'Write a Review', 'Record Video Testimonial',
                    'Take Screenshot Proof', 'Create Short Post',
                ],
            ],
            [
                'name'   => 'Survey & Research',
                'icon'   => 'clipboard-list',
                'status' => 1,
                'subs'   => [
                    'Complete Survey', 'Answer Questions',
                    'Participate in Poll',
                ],
            ],
            [
                'name'   => 'Referral & Invite',
                'icon'   => 'user-plus',
                'status' => 1,
                'subs'   => [
                    'Refer a Friend', 'Invite to Group',
                    'Share Referral Link',
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $subs = $catData['subs'];
            unset($catData['subs']);

            $category = WorkCategory::updateOrCreate(
                ['name' => $catData['name']],
                $catData
            );

            foreach ($subs as $subName) {
                WorkSubcategory::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $subName],
                    ['status' => 1]
                );
            }
        }
    }
}
