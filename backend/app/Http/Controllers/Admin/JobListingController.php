<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\WorkCategory;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobListing::with(['employer', 'category']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $listings   = $query->latest()->paginate(config('jobstation.per_page'))->withQueryString();
        $categories = WorkCategory::orderBy('name')->get();

        $stats = [
            'total'    => JobListing::count(),
            'pending'  => JobListing::where('status', 0)->count(),
            'active'   => JobListing::where('status', 1)->count(),
            'rejected' => JobListing::where('status', 3)->count(),
        ];

        return view('admin.jobs.listings.index', compact('listings', 'categories', 'stats'));
    }

    public function show(int $id)
    {
        $listing = JobListing::with(['employer', 'category', 'subcategory'])
            ->findOrFail($id);

        $applications = JobApplication::where('job_listing_id', $id)
            ->with('applicant')
            ->latest()
            ->paginate(20);

        $appStats = [
            'total'       => JobApplication::where('job_listing_id', $id)->count(),
            'pending'     => JobApplication::where('job_listing_id', $id)->where('status', 0)->count(),
            'shortlisted' => JobApplication::where('job_listing_id', $id)->where('status', 2)->count(),
            'accepted'    => JobApplication::where('job_listing_id', $id)->where('status', 3)->count(),
        ];

        return view('admin.jobs.listings.show', compact('listing', 'applications', 'appStats'));
    }

    public function approve(int $id)
    {
        $listing = JobListing::findOrFail($id);
        $listing->update(['status' => 1, 'rejection_reason' => null]);

        return back()->with('success', 'Job listing approved and is now live.');
    }

    public function reject(Request $request, int $id)
    {
        $listing = JobListing::findOrFail($id);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $listing->update(['status' => 3, 'rejection_reason' => $data['rejection_reason']]);

        return back()->with('success', 'Job listing rejected.');
    }

    public function toggleFeature(int $id)
    {
        $listing = JobListing::findOrFail($id);

        if ($listing->is_featured) {
            $listing->update(['is_featured' => false, 'featured_until' => null]);
            return back()->with('success', 'Listing removed from featured.');
        }

        $days = (int) gs()->boost_days_job;
        $listing->update(['is_featured' => true, 'featured_until' => now()->addDays($days)]);
        return back()->with('success', "Listing featured for {$days} days.");
    }

    public function toggleKyc(int $id)
    {
        $listing = JobListing::findOrFail($id);
        $listing->update(['requires_kyc' => !$listing->requires_kyc]);

        return back()->with('success', $listing->requires_kyc
            ? 'KYC requirement enabled for this listing.'
            : 'KYC requirement removed from this listing.');
    }

    public function destroy(int $id)
    {
        $listing = JobListing::findOrFail($id);

        if ($listing->cover_image) {
            removeFile(config('jobstation.upload_paths.work_cover'), $listing->cover_image);
        }

        $listing->delete();

        return redirect()->route('admin.jobs.listings.index')
            ->with('success', 'Job listing deleted.');
    }
}
