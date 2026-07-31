<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContentSection;
use App\Models\JobListing;
use App\Models\SitePage;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;

class HomeController extends Controller
{
    public function index()
    {
        $gs         = gs();
        $categories = WorkCategory::where('status', 1)->withCount(['works' => fn ($q) => $q->active()])->get();
        $featured     = Work::active()->with(['category'])->latest()->limit(6)->get();

        // Latest blog posts (latest 3)
        $blogPosts = SitePage::where('tempname', 'blog')->latest()->limit(3)->get();

        $stats = [
            'works'      => Work::active()->count(),
            'workers'    => User::where('status', 1)->count(),
            'completed'  => WorkSubmission::where('status', 2)->count(),
            'categories' => WorkCategory::where('status', 1)->count(),
        ];

        // Content sections (editable from admin)
        $hero       = ContentSection::getData('hero')         ?? [];
        $howItWorks = ContentSection::getData('how_it_works') ?? [];
        $features   = ContentSection::getData('features')     ?? [];
        $faq        = ContentSection::getData('faq')          ?? [];
        $cta        = ContentSection::getData('cta')          ?? [];
        $testimonials = ContentSection::getData('testimonials') ?? [];

        return view('web.home', compact(
            'gs', 'categories', 'featured', 'blogPosts', 'stats',
            'hero', 'howItWorks', 'features', 'faq', 'cta', 'testimonials'
        ));
    }
}
