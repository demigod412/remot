<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\WorkCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    private function user()
    {
        return Auth::guard('web')->user();
    }

    // ── Browse Jobs ───────────────────────────────────────────────

    public function browse(Request $request)
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

        $listings   = $query->orderByDesc('is_featured')->latest()->paginate(config('jobstation.per_page'));
        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        $skills     = Skill::active()->orderBy('name')->get();

        // Mark which ones the current user has applied to / bookmarked
        $appliedIds = JobApplication::where('applicant_id', $this->user()->id)
            ->pluck('job_listing_id')
            ->flip();

        $bookmarkedIds = \App\Models\JobBookmark::where('user_id', $this->user()->id)
            ->pluck('job_listing_id')
            ->flip();

        return view('user.jobs.browse', compact('listings', 'categories', 'skills', 'appliedIds', 'bookmarkedIds'));
    }

    public function show(int $id)
    {
        $user    = $this->user();
        $listing = JobListing::open()->with(['category', 'employer'])->findOrFail($id);

        $application  = JobApplication::where('job_listing_id', $id)
            ->where('applicant_id', $user->id)
            ->first();
        $isBookmarked = \App\Models\JobBookmark::where('user_id', $user->id)
            ->where('job_listing_id', $id)
            ->exists();

        return view('user.jobs.show', compact('listing', 'application', 'isBookmarked'));
    }

    // ── Apply ─────────────────────────────────────────────────────

    public function apply(Request $request, int $id)
    {
        $user    = $this->user();
        $listing = JobListing::open()->findOrFail($id);

        // Cannot apply to your own listing
        if ($listing->employer_id === $user->id) {
            return back()->with('error', 'You cannot apply to your own job listing.');
        }

        if ($listing->requires_kyc && $user->kyc_status !== 1) {
            return back()->with('error', 'This job requires KYC verification. Please complete identity verification in your profile before applying.');
        }

        // Already applied
        $exists = JobApplication::where('job_listing_id', $id)
            ->where('applicant_id', $user->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already applied to this job.');
        }

        $data = $request->validate([
            'cover_letter'              => ['required', 'string', 'min:50', 'max:3000'],
            'expected_salary'           => ['nullable', 'numeric', 'min:0'],
            'expected_salary_currency'  => ['nullable', 'string', 'max:10'],
            'portfolio_url'             => ['nullable', 'url', 'max:255'],
            'resume'                    => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $resumeFile = null;
        if ($request->hasFile('resume')) {
            // Résumés are applicant PII — store privately, serve via secure.resume.
            $resumeFile = uploadPrivateFile($request->file('resume'), 'resumes');
        }

        JobApplication::create([
            'job_listing_id'           => $id,
            'applicant_id'             => $user->id,
            'cover_letter'             => $data['cover_letter'],
            'resume'                   => $resumeFile,
            'portfolio_url'            => $data['portfolio_url'] ?? null,
            'expected_salary'          => $data['expected_salary'] ?? null,
            'expected_salary_currency' => $data['expected_salary_currency'] ?? null,
            'status'                   => 0,
        ]);

        $listing->increment('application_count');

        return redirect()->route('user.jobs.my-applications')
            ->with('success', 'Application submitted successfully! The employer will be in touch.');
    }

    // ── My Applications ───────────────────────────────────────────

    public function myApplications(Request $request)
    {
        $user  = $this->user();
        $query = JobApplication::where('applicant_id', $user->id)
            ->with(['listing.employer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.jobs.my-applications', compact('applications'));
    }

    public function withdraw(int $id)
    {
        $user = $this->user();
        $app  = JobApplication::where('id', $id)
            ->where('applicant_id', $user->id)
            ->where('status', 0) // can only withdraw pending applications
            ->firstOrFail();

        $app->listing()->decrement('application_count');
        $app->delete();

        return back()->with('success', 'Application withdrawn.');
    }
}
