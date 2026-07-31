<?php

namespace Database\Seeders;

use App\Models\JobListing;
use App\Models\SitePage;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users set coin_balance directly; it is intentionally non-fillable,
        // so unguard mass-assignment for the duration of this (trusted) seeder.
        \Illuminate\Database\Eloquent\Model::unguard();

        // ── Demo Worker (KYC unverified — for testing submission flow) ─
        $worker = User::updateOrCreate(
            ['email' => 'worker@demo.com'],
            [
                'firstname'      => 'Alex',
                'lastname'       => 'Mwangi',
                'username'       => 'alex_mwangi',
                'password'       => Hash::make('Demo@1234'),
                'status'         => 1,
                'email_verified' => 1,
                'kyc_status'     => 0,
                'kyc_data'       => null,
                'coin_balance'   => 450,
            ]
        );

        // ── Demo Employer (KYC verified) ──────────────────────────────
        $employer = User::updateOrCreate(
            ['email' => 'employer@demo.com'],
            [
                'firstname'      => 'Sarah',
                'lastname'       => 'Kariuki',
                'username'       => 'sarah_kariuki',
                'password'       => Hash::make('Demo@1234'),
                'status'         => 1,
                'email_verified' => 1,
                'kyc_status'     => 1,
                'coin_balance'   => 2000,
            ]
        );

        // ── Extra KYC-verified users ──────────────────────────────────
        $verifiedWorker2 = User::updateOrCreate(
            ['email' => 'james.otieno@demo.com'],
            [
                'firstname'      => 'James',
                'lastname'       => 'Otieno',
                'username'       => 'james_otieno',
                'password'       => Hash::make('Demo@1234'),
                'status'         => 1,
                'email_verified' => 1,
                'kyc_status'     => 1,
                'coin_balance'   => 180,
                'kyc_data'       => [
                    'document_type' => 'National ID',
                    'front_image'   => 'sample_id_front.jpeg',
                    'back_image'    => 'sample_id_back.jpeg',
                    'submitted_at'  => now()->subDays(5)->toISOString(),
                ],
            ]
        );

        $verifiedWorker3 = User::updateOrCreate(
            ['email' => 'fatuma.hassan@demo.com'],
            [
                'firstname'      => 'Fatuma',
                'lastname'       => 'Hassan',
                'username'       => 'fatuma_hassan',
                'password'       => Hash::make('Demo@1234'),
                'status'         => 1,
                'email_verified' => 1,
                'kyc_status'     => 1,
                'coin_balance'   => 920,
                'kyc_data'       => [
                    'document_type' => 'Passport',
                    'front_image'   => 'sample_id_front.jpeg',
                    'submitted_at'  => now()->subDays(10)->toISOString(),
                ],
            ]
        );

        // ── KYC Pending user (just submitted docs) ────────────────────
        User::updateOrCreate(
            ['email' => 'brian.njoroge@demo.com'],
            [
                'firstname'      => 'Brian',
                'lastname'       => 'Njoroge',
                'username'       => 'brian_njoroge',
                'password'       => Hash::make('Demo@1234'),
                'status'         => 1,
                'email_verified' => 1,
                'kyc_status'     => 2,
                'coin_balance'   => 75,
                'kyc_data'       => [
                    'document_type' => 'National ID',
                    'front_image'   => 'sample_id_front.jpeg',
                    'back_image'    => 'sample_id_back.jpeg',
                    'submitted_at'  => now()->subMinutes(12)->toISOString(),
                ],
            ]
        );

        // ── Categories ────────────────────────────────────────────────
        $social  = WorkCategory::where('name', 'Social Media')->first();
        $app     = WorkCategory::where('name', 'App & Play Store')->first();
        $web     = WorkCategory::where('name', 'Website & Traffic')->first();
        $content = WorkCategory::where('name', 'Content Creation')->first();
        $survey  = WorkCategory::where('name', 'Survey & Research')->first();

        $sid = $social?->id  ?? 1;
        $aid = $app?->id     ?? 2;
        $wid = $web?->id     ?? 3;
        $cid = $content?->id ?? 4;
        $vid = $survey?->id  ?? 5;

        // ── Instant Works (posted by admin) ───────────────────────────
        $instantWorks = [
            [
                'title'            => 'Follow our Instagram page and like 3 posts',
                'category_id'      => $sid,
                'worker_slots'     => 150,
                'coins_per_worker' => 12,
                'avg_minutes'      => 5,
                'description'      => '<p>We need real followers on our Instagram page. Please follow the page and like any 3 posts of your choice.</p><h3>Steps</h3><ol><li>Visit <strong>@jobstation</strong> on Instagram</li><li>Follow the account</li><li>Like 3 posts</li><li>Take a screenshot showing you followed and the 3 likes</li></ol><h3>Proof Required</h3><p>Screenshot clearly showing your username, the followed state, and the liked posts.</p>',
            ],
            [
                'title'            => 'Subscribe to our YouTube channel and watch a video for 3 minutes',
                'category_id'      => $sid,
                'worker_slots'     => 200,
                'coins_per_worker' => 15,
                'avg_minutes'      => 6,
                'description'      => '<p>Help us grow our YouTube channel. Subscribe and watch one of our videos for at least 3 minutes.</p><h3>Steps</h3><ol><li>Go to our YouTube channel: <strong>Job Station Marketplace</strong></li><li>Subscribe to the channel</li><li>Watch any video for at least 3 minutes</li><li>Screenshot the subscribed state and video progress bar</li></ol><h3>Proof Required</h3><p>Screenshot showing subscribed status and video watch time.</p>',
            ],
            [
                'title'            => 'Install our mobile app and sign up',
                'category_id'      => $aid,
                'worker_slots'     => 100,
                'coins_per_worker' => 25,
                'avg_minutes'      => 8,
                'description'      => '<p>Download and install our mobile app from the Play Store, then create a free account.</p><h3>Steps</h3><ol><li>Search "Job Station App" on Play Store</li><li>Install the app</li><li>Sign up with your email</li><li>Complete the onboarding flow</li><li>Screenshot the home screen showing your username</li></ol><h3>Proof Required</h3><p>Screenshot of the app home screen while logged in to your account.</p>',
            ],
            [
                'title'            => 'Rate and review our app on Play Store',
                'category_id'      => $aid,
                'worker_slots'     => 80,
                'coins_per_worker' => 20,
                'avg_minutes'      => 5,
                'description'      => '<p>Leave an honest 5-star rating and a written review (at least 20 words) on our Play Store listing.</p><h3>Steps</h3><ol><li>Open Play Store and search "Job Station App"</li><li>Tap Rate this app</li><li>Give 5 stars</li><li>Write a review of at least 20 words</li><li>Submit and screenshot the confirmation</li></ol><h3>Proof Required</h3><p>Screenshot of your submitted review with your Play Store profile name visible.</p>',
            ],
            [
                'title'            => 'Visit our website and spend 3 minutes browsing',
                'category_id'      => $wid,
                'worker_slots'     => 300,
                'coins_per_worker' => 8,
                'avg_minutes'      => 5,
                'description'      => '<p>Visit our website and browse through at least 3 pages, spending a minimum of 3 minutes total.</p><h3>Steps</h3><ol><li>Open jobstation.example.com in your browser</li><li>Browse the homepage, blog, and find-work pages</li><li>Spend at least 3 minutes on the site</li><li>Screenshot the browser showing the URL and one of the pages</li></ol><h3>Proof Required</h3><p>Screenshot showing the website URL in the address bar.</p>',
            ],
            [
                'title'            => 'Write a Google review for our business',
                'category_id'      => $cid,
                'worker_slots'     => 60,
                'coins_per_worker' => 35,
                'avg_minutes'      => 10,
                'description'      => '<p>Write an honest review (at least 30 words) on Google Maps for our business listing.</p><h3>Steps</h3><ol><li>Search "Job Station Marketplace" on Google Maps</li><li>Click Write a Review</li><li>Give your honest rating and write at least 30 words</li><li>Submit the review</li></ol><h3>Proof Required</h3><p>Screenshot of your published Google review showing your Google account name.</p>',
            ],
            [
                'title'            => 'Complete our user experience survey',
                'category_id'      => $vid,
                'worker_slots'     => 500,
                'coins_per_worker' => 18,
                'avg_minutes'      => 10,
                'description'      => '<p>Help us improve by completing a short 10-question survey about your experience with online marketplaces.</p><h3>Steps</h3><ol><li>Open the survey link provided after starting this task</li><li>Answer all 10 questions honestly</li><li>Submit the form</li><li>Screenshot the confirmation / thank-you page</li></ol><h3>Proof Required</h3><p>Screenshot of the survey confirmation page with your submission ID visible.</p>',
            ],
            [
                'title'            => 'Share our landing page on your Facebook profile',
                'category_id'      => $sid,
                'worker_slots'     => 120,
                'coins_per_worker' => 10,
                'avg_minutes'      => 3,
                'description'      => '<p>Share our website link publicly on your Facebook profile with a short caption.</p><h3>Steps</h3><ol><li>Go to your Facebook profile</li><li>Create a new post</li><li>Paste the link: jobstation.example.com</li><li>Add a caption (e.g. "Found great micro-jobs here!")</li><li>Set post visibility to Public</li><li>Screenshot the published post</li></ol><h3>Proof Required</h3><p>Screenshot of the published post with your profile name visible and visibility set to Public.</p>',
            ],
            [
                'title'            => 'Sign up on our partner website using your email',
                'category_id'      => $wid,
                'worker_slots'     => 80,
                'coins_per_worker' => 22,
                'avg_minutes'      => 7,
                'description'      => '<p>Register a new account on our partner website using your real email address and complete profile setup.</p><h3>Steps</h3><ol><li>Visit the partner website link (provided after task start)</li><li>Click Sign Up and register with your email</li><li>Verify your email address</li><li>Complete the profile setup form</li><li>Screenshot the completed dashboard showing your username</li></ol><h3>Proof Required</h3><p>Screenshot of the welcome/dashboard page after successful sign-up, showing your username or email.</p>',
            ],
            [
                'title'            => 'Leave a comment on our latest blog post',
                'category_id'      => $cid,
                'worker_slots'     => 200,
                'coins_per_worker' => 8,
                'avg_minutes'      => 4,
                'description'      => '<p>Read our latest blog post and leave a thoughtful comment (at least 15 words) using your real name.</p><h3>Steps</h3><ol><li>Go to the blog section of our website</li><li>Open the latest post</li><li>Scroll to the comments section</li><li>Write a genuine comment of at least 15 words</li><li>Submit and screenshot your comment once published</li></ol><h3>Proof Required</h3><p>Screenshot of your published comment showing your name and the blog post title.</p>',
            ],
        ];

        foreach ($instantWorks as $data) {
            $slug = Str::slug($data['title']);
            Work::updateOrCreate(['slug' => $slug], array_merge($data, [
                'slug'            => $slug,
                'total_coins'     => $data['coins_per_worker'] * $data['worker_slots'],
                'work_status'     => 1,
                'approval_status' => 1,
                'poster_id'       => 1,
                'poster_type'     => 1,
            ]));
        }

        // ── Timed Works (have expires_at countdown) ───────────────────
        $timedWorks = [
            [
                'title'            => 'Like and retweet our pinned tweet — 24hr campaign',
                'category_id'      => $sid,
                'worker_slots'     => 50,
                'coins_per_worker' => 14,
                'avg_minutes'      => 3,
                'expires_at'       => now()->addHours(24),
                'description'      => '<p>This is a time-limited campaign. Like and retweet our pinned tweet before the deadline.</p><h3>Steps</h3><ol><li>Visit <strong>@jobstation</strong> on Twitter/X</li><li>Like the pinned tweet</li><li>Retweet it</li><li>Screenshot showing your profile, the like, and retweet state</li></ol><h3>Proof Required</h3><p>Screenshot showing your username and the liked + retweeted state on the post.</p>',
            ],
            [
                'title'            => 'Join our Telegram channel — 48hr flash task',
                'category_id'      => $sid,
                'worker_slots'     => 200,
                'coins_per_worker' => 10,
                'avg_minutes'      => 2,
                'expires_at'       => now()->addHours(48),
                'description'      => '<p>Flash task — only 48 hours. Join our official Telegram channel and stay for at least 24 hours.</p><h3>Steps</h3><ol><li>Search "Job Station Marketplace" on Telegram</li><li>Click Join Channel</li><li>Screenshot the channel showing your membership</li></ol><h3>Proof Required</h3><p>Screenshot showing the channel name and your joined status.</p>',
            ],
            [
                'title'            => 'Download and open our app — weekend sprint',
                'category_id'      => $aid,
                'worker_slots'     => 75,
                'coins_per_worker' => 30,
                'avg_minutes'      => 6,
                'expires_at'       => now()->addDays(3),
                'description'      => '<p>Limited weekend campaign. Download the app, open it, and reach the home screen.</p><h3>Steps</h3><ol><li>Search "Job Station App" on Play Store or App Store</li><li>Download and install</li><li>Open the app and reach the home/onboarding screen</li><li>Screenshot the app home screen</li></ol><h3>Proof Required</h3><p>Screenshot of the app open on your device with the store icon visible.</p>',
            ],
        ];

        foreach ($timedWorks as $data) {
            $slug = Str::slug($data['title']);
            Work::updateOrCreate(['slug' => $slug], array_merge($data, [
                'slug'            => $slug,
                'total_coins'     => $data['coins_per_worker'] * $data['worker_slots'],
                'work_status'     => 1,
                'approval_status' => 1,
                'poster_id'       => 1,
                'poster_type'     => 1,
            ]));
        }

        // ── KYC-Required Works ────────────────────────────────────────
        $kycWorks = [
            [
                'title'            => 'Complete a paid product review — verified users only',
                'category_id'      => $cid,
                'worker_slots'     => 30,
                'coins_per_worker' => 80,
                'avg_minutes'      => 20,
                'requires_kyc'     => true,
                'description'      => '<p>This task requires KYC verification. Write a detailed 100-word product review on our partner e-commerce site.</p><h3>Steps</h3><ol><li>Visit the product page link provided after starting</li><li>Write an honest review of at least 100 words</li><li>Submit the review</li><li>Screenshot your published review with your profile name visible</li></ol><h3>Proof Required</h3><p>Screenshot of your published review showing your display name and the review content.</p>',
            ],
            [
                'title'            => 'Participate in our paid focus group — KYC required',
                'category_id'      => $vid,
                'worker_slots'     => 15,
                'coins_per_worker' => 150,
                'avg_minutes'      => 30,
                'requires_kyc'     => true,
                'description'      => '<p>Join our 30-minute online focus group session. KYC verification is required to ensure participant authenticity.</p><h3>Steps</h3><ol><li>Register your time slot using the link provided</li><li>Join the Zoom call at the scheduled time</li><li>Participate for the full 30 minutes</li><li>Screenshot the session confirmation email after completion</li></ol><h3>Proof Required</h3><p>Screenshot of your session confirmation or atjobstationnce certificate.</p>',
            ],
        ];

        foreach ($kycWorks as $data) {
            $slug = Str::slug($data['title']);
            Work::updateOrCreate(['slug' => $slug], array_merge($data, [
                'slug'            => $slug,
                'total_coins'     => $data['coins_per_worker'] * $data['worker_slots'],
                'work_status'     => 1,
                'approval_status' => 1,
                'poster_id'       => 1,
                'poster_type'     => 1,
            ]));
        }

        // ── Hiring Jobs (posted by demo employer) ─────────────────────
        $hiringJobs = [
            [
                'title'           => 'Remote Social Media Manager',
                'category_id'     => $sid,
                'description'     => '<p>We are looking for a creative and driven Social Media Manager to join our growing team. You will manage all social channels, create engaging content, and grow our online presence.</p><h3>Responsibilities</h3><ul><li>Manage Facebook, Instagram, Twitter, and LinkedIn daily</li><li>Create and schedule posts, reels, and stories</li><li>Respond to comments and messages promptly</li><li>Track analytics and report weekly growth metrics</li><li>Collaborate with the content team on campaigns</li></ul><h3>Requirements</h3><ul><li>2+ years social media management experience</li><li>Strong copywriting skills in English and Swahili</li><li>Familiarity with Canva, Buffer, or Hootsuite</li><li>Self-motivated with good time management</li></ul>',
                'location'        => 'Remote',
                'location_type'   => 1,
                'employment_type' => 'full_time',
                'salary_min'      => 800,
                'salary_max'      => 1200,
            ],
            [
                'title'           => 'Junior Frontend Web Developer',
                'category_id'     => $wid,
                'description'     => '<p>We are hiring a Junior Frontend Developer to help build beautiful, responsive web interfaces. You will work closely with our design and backend teams to bring designs to life.</p><h3>Responsibilities</h3><ul><li>Build responsive pages using HTML, CSS, and JavaScript</li><li>Maintain and improve existing codebases</li><li>Collaborate with designers on UI/UX improvements</li><li>Write clean, maintainable code</li></ul><h3>Requirements</h3><ul><li>Knowledge of HTML5, CSS3, and JavaScript (ES6+)</li><li>Familiarity with React or Vue.js is a plus</li><li>Understanding of responsive design and cross-browser compatibility</li><li>1+ year experience or a strong portfolio</li></ul>',
                'location'        => 'Nairobi, Kenya',
                'location_type'   => 3,
                'employment_type' => 'full_time',
                'salary_min'      => 500,
                'salary_max'      => 900,
            ],
            [
                'title'           => 'Content Writer — Tech & Finance',
                'category_id'     => $cid,
                'description'     => '<p>We need a talented content writer to produce high-quality blog posts, articles, and guides covering freelancing, technology, personal finance, and the gig economy.</p><h3>Responsibilities</h3><ul><li>Research and write 4-6 articles per week (800-2000 words each)</li><li>Optimize content for SEO using provided keywords</li><li>Edit and proofread before submission</li><li>Suggest new topic ideas based on trends</li></ul><h3>Requirements</h3><ul><li>Excellent written English</li><li>Experience writing for digital audiences</li><li>Basic understanding of SEO principles</li><li>Ability to meet deadlines consistently</li></ul>',
                'location'        => 'Remote',
                'location_type'   => 1,
                'employment_type' => 'freelance',
                'salary_min'      => 300,
                'salary_max'      => 600,
            ],
            [
                'title'           => 'Part-Time Customer Support Agent',
                'category_id'     => $wid,
                'description'     => '<p>Join our support team and help users resolve issues quickly and professionally. You will handle email and live-chat support, escalate complex issues, and maintain Help Center documentation.</p><h3>Responsibilities</h3><ul><li>Respond to emails and chats within 2 hours during your shift</li><li>Resolve account, payment, and technical issues</li><li>Document recurring issues and suggest product improvements</li><li>Update Help Center articles as needed</li></ul><h3>Requirements</h3><ul><li>Strong written communication skills</li><li>Patient and empathetic customer-service attitude</li><li>Basic technical aptitude</li><li>Available 4-6 hours per day, Monday–Saturday</li></ul>',
                'location'        => 'Remote',
                'location_type'   => 1,
                'employment_type' => 'part_time',
                'salary_min'      => 250,
                'salary_max'      => 400,
            ],
            [
                'title'           => 'Data Entry Specialist (Contract)',
                'category_id'     => $vid,
                'description'     => '<p>We have an ongoing data entry project and need a detail-oriented specialist to process and organize large datasets accurately.</p><h3>Responsibilities</h3><ul><li>Enter data from various sources into spreadsheets and internal systems</li><li>Verify and clean existing data records</li><li>Flag inconsistencies or errors for review</li><li>Maintain confidentiality of all data</li></ul><h3>Requirements</h3><ul><li>High attention to detail and accuracy</li><li>Proficiency in Excel or Google Sheets</li><li>Typing speed of at least 40 WPM</li><li>Reliable internet connection</li></ul>',
                'location'        => 'Remote',
                'location_type'   => 1,
                'employment_type' => 'contract',
                'salary_min'      => 200,
                'salary_max'      => 350,
            ],
            [
                'title'           => 'Graphic Designer — Brand & Marketing',
                'category_id'     => $cid,
                'description'     => '<p>We\'re looking for a creative Graphic Designer to craft compelling visual assets for our brand — social media graphics, email banners, pitch decks, and more.</p><h3>Responsibilities</h3><ul><li>Design social media graphics, banners, and promotional materials</li><li>Maintain brand consistency across all visual assets</li><li>Collaborate with the marketing team on campaign visuals</li><li>Deliver assets in multiple formats as required</li></ul><h3>Requirements</h3><ul><li>Proficiency in Adobe Illustrator, Photoshop, or Figma</li><li>Strong portfolio of brand and marketing work</li><li>Eye for typography and color theory</li><li>Ability to handle multiple projects simultaneously</li></ul>',
                'location'        => 'Remote',
                'location_type'   => 1,
                'employment_type' => 'freelance',
                'salary_min'      => 400,
                'salary_max'      => 700,
            ],
        ];

        foreach ($hiringJobs as $data) {
            $slug = Str::slug($data['title']);
            JobListing::updateOrCreate(['slug' => $slug], array_merge($data, [
                'slug'            => $slug,
                'employer_id'     => $employer->id,
                'salary_currency' => 'USD',
                'salary_visible'  => 1,
                'status'          => 1,
            ]));
        }

        // ── Blog Posts ────────────────────────────────────────────────
        $blogPosts = [
            [
                'name'     => 'Earn Your First 100 Coins on Job Station',
                'slug'     => 'how-to-earn-first-100-coins-on-jobstation',
                'secs'     => [[
                    'tag'     => 'Workers',
                    'excerpt' => 'Getting started on Job Station is simple. Here\'s a step-by-step guide to completing your first tasks and reaching that 100-coin milestone fast.',
                    'content' => '<p>If you\'ve just signed up on Job Station, you\'re in the right place. The marketplace has hundreds of instant tasks waiting to be completed, and reaching your first 100 coins is easier than you think.</p><h2>Start with easy tasks</h2><p>Browse the <strong>Instant Jobs</strong> tab and filter by "Lowest reward" — these tasks are typically the simplest and best for new workers still learning the platform. Tasks like following a social media account or visiting a website take 3-5 minutes and pay 8-15 coins each.</p><h2>Build a reputation early</h2><p>Your submission approval rate matters. Clients who see a high approval rate accept your proof quickly. Always read task instructions carefully before starting, take clear screenshots, and never submit blurry or cropped proof.</p><h2>Complete your profile</h2><p>Workers with completed profiles — a photo, bio, and verified email — are shown first in search results and get priority on competitive tasks with limited slots. Spend 5 minutes completing your profile before applying to any tasks.</p><h2>Track your progress</h2><p>Your dashboard shows earnings in real time. Once you hit 100 coins you can request a cashout to your mobile money wallet. The minimum cashout is 50 coins. Good luck, and welcome to the Job Station community!</p>',
                ]],
            ],
            [
                'name'     => 'Why Businesses Choose Job Station Tasks',
                'slug'     => 'why-businesses-choose-jobstation-tasks',
                'secs'     => [[
                    'tag'     => 'Clients',
                    'excerpt' => 'From social proof to app reviews, Job Station gives businesses a reliable, cost-effective way to run authentic engagement campaigns at scale.',
                    'content' => '<p>When it comes to building authentic engagement online, fake bots and purchased followers are a liability. Job Station connects businesses with real, verified workers who complete tasks genuinely — and the results speak for themselves.</p><h2>Real people, real results</h2><p>Every worker on Job Station is a verified human with a profile history. When you post a task you\'re reaching thousands of real users across East Africa and beyond. Your reviews are real. Your followers are real. Your app installs count.</p><h2>Pay only for approved results</h2><p>Unlike traditional ad platforms where you pay for impressions, Job Station operates on a results basis. You load coins, set your task criteria, and only pay when a worker submits acceptable proof. Reject anything that doesn\'t meet your requirements — no charge.</p><h2>Launch in minutes</h2><p>Creating a task takes less than 2 minutes. Set your title, describe the steps, choose how many workers you need, set the reward per worker. Your task goes live immediately after admin approval — typically within 30 minutes.</p><h2>Full control and visibility</h2><p>Your campaign dashboard shows live submission counts, proof files for every worker, and your remaining budget. Pause, edit, or close tasks at any time. Unused coins stay in your balance for future campaigns.</p>',
                ]],
            ],
            [
                'name'     => 'Hiring Jobs & Contracts Now Live',
                'slug'     => 'hiring-jobs-contracts-now-live',
                'secs'     => [[
                    'tag'     => 'Product',
                    'excerpt' => 'We\'ve expanded beyond instant microtasks. Job Station now supports full hiring job listings and private contract engagements between verified users.',
                    'content' => '<p>We started Job Station with a simple idea: make it easy to complete small tasks and get paid quickly. Today we\'re excited to announce that Job Station has expanded into a full job marketplace with two powerful new features.</p><h2>Hiring Jobs</h2><p>Employers can now post full job listings on Job Station — remote positions, part-time roles, freelance contracts, and on-site jobs. Each listing can include salary ranges, location requirements, employment type, and required skills. Workers browse and apply with a cover letter and their Job Station profile.</p><p>Job listings are visible to all users under the <strong>Hiring Jobs</strong> tab on the Find Work page.</p><h2>Private Contracts</h2><p>For longer-term or sensitive engagements, our Contracts feature lets two verified users set up a private, escrow-backed agreement. The employer loads funds into escrow, the worker delivers, and payment is released on completion. Disputes are handled by our support team.</p><h2>What stays the same</h2><p>Instant tasks remain at the core of Job Station. All three modes — Instant, Hiring, and Contract — are accessible from a single unified interface.</p>',
                ]],
            ],
            [
                'name'     => '5 Tips for Better Task Descriptions',
                'slug'     => '5-tips-better-task-descriptions',
                'secs'     => [[
                    'tag'     => 'Tutorials',
                    'excerpt' => 'The quality of your task description directly affects how fast workers apply and how accurate their submissions are. Here\'s how to write one that converts.',
                    'content' => '<p>A poorly written task description leads to confused workers, bad submissions, and wasted coins. A great one fills your slots in under an hour with exactly the proof you need. Here are five tips from our top clients.</p><h2>1. Lead with the outcome, not the steps</h2><p>Your title and first sentence should immediately communicate what you\'re trying to achieve. "Follow our Instagram page" is better than "We need help with our social media presence."</p><h2>2. Number your steps</h2><p>Use a numbered list for every step the worker must take. This reduces back-and-forth, makes proof-checking easier, and cuts rejection rates. Workers know exactly what to do and in what order.</p><h2>3. Be specific about proof requirements</h2><p>Don\'t say "take a screenshot." Say "take a screenshot showing your username, the followed/subscribed status, and the page name in the same frame." The more specific your proof requirements, the fewer bad submissions you\'ll receive.</p><h2>4. Set a fair reward</h2><p>Tasks that pay 25+ coins fill faster than tasks paying 5 coins for the same effort. Use the platform\'s category benchmarks as a guide. Fair pay attracts experienced workers.</p><h2>5. Test your task yourself first</h2><p>Walk through your own task steps as if you were a worker. Can you complete it in the time you estimated? Is the proof you\'re asking for actually possible to provide? This one step catches 80% of common errors.</p>',
                ]],
            ],
            [
                'name'     => 'Workers Who Earn Full-Time on Job Station',
                'slug'     => 'workers-earning-full-time-on-jobstation',
                'secs'     => [[
                    'tag'     => 'Workers',
                    'excerpt' => 'We sat down with three Job Station workers who turned microtask earnings into a reliable income stream. Here\'s what they learned.',
                    'content' => '<p>For many of our workers, Job Station started as a way to earn a little extra cash between gigs. For some, it\'s become much more. We talked to three of our top workers about their journeys.</p><h2>Amina, 26 — Mombasa</h2><p>"I started doing 5-6 tasks a day after work. After two months I was consistently earning more from Job Station than from my part-time job. I\'ve now quit the part-time job and do Job Station full time alongside a few private contracts I got through the platform."</p><p>Amina\'s tip: <em>"Focus on tasks you can do fast without sacrificing quality. Social media tasks are the easiest for me because I\'m already on those platforms all day."</em></p><h2>Kevin, 31 — Nairobi</h2><p>"I was skeptical at first — I thought it was one of those fake task sites. But after my first cashout hit my M-Pesa within 24 hours, I was sold. I\'ve now referred 14 friends and we have a WhatsApp group where we share available tasks."</p><p>Kevin\'s tip: <em>"Don\'t skip reading the task description. Workers who get rejected waste time and hurt their approval rate. Read everything before you start."</em></p><h2>Grace, 22 — Dar es Salaam</h2><p>"I used my Job Station earnings to pay for a graphic design course. Now I pick up design contracts through the platform too — it funded my own skill upgrade."</p><p>Grace\'s tip: <em>"Apply for task slots early. Popular tasks fill up fast. I check the platform first thing every morning."</em></p>',
                ]],
            ],
            [
                'name'     => 'How Job Station Handles Submission Disputes',
                'slug'     => 'how-jobstation-handles-submission-disputes',
                'secs'     => [[
                    'tag'     => 'Product',
                    'excerpt' => 'Fairness is at the core of how Job Station works. Here\'s exactly what happens when a worker or client raises a dispute over a submission.',
                    'content' => '<p>Every marketplace has disputes. Ours are rare, but when they happen we handle them with a clear, consistent process that protects both workers and clients.</p><h2>When a worker disputes a rejection</h2><p>If your submission is rejected and you believe you followed all the task instructions correctly, open a support ticket with your original proof attached. Our team reviews the task requirements against your submission and makes a final decision within 48 hours. If the rejection was unjustified, we credit your account with the full task reward and flag the client\'s account for review.</p><h2>When a client disputes a submission</h2><p>Clients have 72 hours to review each submission. If you\'re unsure whether a submission meets your criteria, use the "Request More Info" option to ask the worker for clarification before rejecting. If a client rejects a submission our team deems compliant, the worker is credited and the client receives a warning. Repeated unjustified rejections can result in account suspension.</p><h2>Our commitment</h2><p>Job Station\'s dispute resolution is handled by a dedicated team — not an algorithm. We read every ticket, look at every proof file, and make judgment calls based on the actual task description. Our goal is a marketplace where workers trust that good work is rewarded, and clients trust that they only pay for genuine results.</p>',
                ]],
            ],
        ];

        foreach ($blogPosts as $data) {
            SitePage::updateOrCreate(
                ['slug' => $data['slug'], 'tempname' => 'blog'],
                array_merge($data, ['tempname' => 'blog'])
            );
        }

        $this->command->info('Demo data seeded:');
        $this->command->info("  Worker        → worker@demo.com          / Demo@1234  (KYC verified)");
        $this->command->info("  Employer      → employer@demo.com         / Demo@1234  (KYC verified)");
        $this->command->info("  KYC verified  → james.otieno@demo.com     / Demo@1234");
        $this->command->info("  KYC verified  → fatuma.hassan@demo.com    / Demo@1234");
        $this->command->info("  KYC pending   → brian.njoroge@demo.com    / Demo@1234  (just submitted docs)");
        $this->command->info("  10 instant works, 3 timed works, 2 KYC-required works, 6 hiring jobs, 6 blog posts");

        \Illuminate\Database\Eloquent\Model::reguard();
    }
}
