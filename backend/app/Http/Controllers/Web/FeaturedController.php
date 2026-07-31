<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Work;

class FeaturedController extends Controller
{
    public function index()
    {
        $featuredWorks = Work::active()->featured()->with(['category'])->latest()->get();
        $featuredJobs  = JobListing::where('status', 1)->featured()->with(['employer', 'category'])->latest()->get();

        return view('web.featured', compact('featuredWorks', 'featuredJobs'));
    }
}
