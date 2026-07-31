<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\WorkCategory;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::open()->with(['category', 'employer']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($skillId = $request->input('skill')) {
            $query->whereHas('skills', fn($q) => $q->where('skills.id', $skillId));
        }

        $listings   = $query->orderByRaw('(is_featured = 1 AND (featured_until IS NULL OR featured_until > NOW())) DESC')->latest()->paginate(15)->withQueryString();
        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        $skills     = Skill::active()->orderBy('name')->get();

        return view('web.jobs.index', compact('listings', 'categories', 'skills'));
    }

    public function show(string $slug)
    {
        $job = JobListing::open()
            ->with(['category', 'employer', 'skills'])
            ->where('slug', $slug)
            ->firstOrFail();

        $similar = JobListing::open()
            ->where('category_id', $job->category_id)
            ->where('id', '!=', $job->id)
            ->limit(4)
            ->get();

        return view('web.jobs.show', compact('job', 'similar'));
    }
}
