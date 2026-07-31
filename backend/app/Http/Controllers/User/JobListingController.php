<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\BoostRequest;
use App\Models\JobListing;
use App\Models\WorkCategory;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobListingController extends Controller
{
    private function employer()
    {
        return Auth::guard('web')->user();
    }

    // ── My Listings ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = $this->employer();
        $query = $user->jobListings()->with('category');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $listings = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.jobs.listings.index', compact('listings'));
    }

    public function create()
    {
        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        return view('user.jobs.listings.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = $this->employer();

        $data = $request->validate([
            'title'                    => ['required', 'string', 'max:160'],
            'category_id'              => ['required', 'exists:work_categories,id'],
            'subcategory_id'           => ['nullable', 'exists:work_subcategories,id'],
            'description'              => ['required', 'string', 'min:50'],
            'location'                 => ['nullable', 'string', 'max:120'],
            'location_type'            => ['required', 'in:1,2,3'],
            'employment_type'          => ['required', 'in:full_time,part_time,contract,freelance'],
            'salary_min'               => ['nullable', 'numeric', 'min:0'],
            'salary_max'               => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'salary_currency'          => ['required', 'string', 'max:10'],
            'salary_visible'           => ['boolean'],
            'requirements'             => ['nullable', 'string'],
            'benefits'                 => ['nullable', 'string'],
            'closes_at'                => ['nullable', 'date', 'after:today'],
            'cover_image'              => ['nullable', 'image', 'max:2048'],
            'requires_kyc'             => ['sometimes', 'boolean'],
        ]);

        $coverImage = null;
        if ($request->hasFile('cover_image')) {
            $coverImage = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        JobListing::create([
            'employer_id'     => $user->id,
            'category_id'     => $data['category_id'],
            'subcategory_id'  => $data['subcategory_id'] ?? null,
            'title'           => $data['title'],
            'slug'            => JobListing::generateSlug($data['title']),
            'description'     => $data['description'],
            'location'        => $data['location'] ?? null,
            'location_type'   => $data['location_type'],
            'employment_type' => $data['employment_type'],
            'salary_min'      => $data['salary_min'] ?? null,
            'salary_max'      => $data['salary_max'] ?? null,
            'salary_currency' => $data['salary_currency'],
            'salary_visible'  => $request->boolean('salary_visible'),
            'requirements'    => $data['requirements'] ?? null,
            'benefits'        => $data['benefits'] ?? null,
            'cover_image'     => $coverImage,
            'closes_at'       => $data['closes_at'] ?? null,
            'requires_kyc'    => $request->boolean('requires_kyc'),
            'status'          => 0, // pending admin approval
        ]);

        $newListing = JobListing::where('employer_id', $user->id)->latest()->first();
        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Job Listing Pending Review',
                "{$user->username} submitted a new job listing: \"{$data['title']}\"",
                'info', $newListing ? route('admin.jobs.listings.show', $newListing->id) : null);
        }

        return redirect()->route('user.jobs.listings.index')
            ->with('success', 'Job listing submitted for review. We\'ll notify you once it\'s approved.');
    }

    public function show(int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->with(['category', 'applications.applicant'])
            ->firstOrFail();

        $appStats = [
            'total'       => $listing->applications()->count(),
            'pending'     => $listing->applications()->where('status', 0)->count(),
            'shortlisted' => $listing->applications()->where('status', 2)->count(),
            'accepted'    => $listing->applications()->where('status', 3)->count(),
        ];

        $applications = $listing->applications()->with('applicant')->latest()->paginate(20);

        $pendingBoost = $listing->boostRequests()->where('status', 0)->latest()->first();

        return view('user.jobs.listings.show', compact('listing', 'appStats', 'applications', 'pendingBoost'));
    }

    public function edit(int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->whereIn('status', [0, 3]) // can only edit pending or rejected
            ->firstOrFail();

        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        return view('user.jobs.listings.edit', compact('listing', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->whereIn('status', [0, 3])
            ->firstOrFail();

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:160'],
            'category_id'     => ['required', 'exists:work_categories,id'],
            'subcategory_id'  => ['nullable', 'exists:work_subcategories,id'],
            'description'     => ['required', 'string', 'min:50'],
            'location'        => ['nullable', 'string', 'max:120'],
            'location_type'   => ['required', 'in:1,2,3'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,freelance'],
            'salary_min'      => ['nullable', 'numeric', 'min:0'],
            'salary_max'      => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'salary_currency' => ['required', 'string', 'max:10'],
            'salary_visible'  => ['boolean'],
            'requirements'    => ['nullable', 'string'],
            'benefits'        => ['nullable', 'string'],
            'closes_at'       => ['nullable', 'date', 'after:today'],
            'cover_image'     => ['nullable', 'image', 'max:2048'],
            'requires_kyc'    => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('cover_image')) {
            if ($listing->cover_image) {
                removeFile(config('jobstation.upload_paths.work_cover'), $listing->cover_image);
            }
            $data['cover_image'] = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        $listing->update(array_merge($data, [
            'salary_visible'   => $request->boolean('salary_visible'),
            'subcategory_id'   => $data['subcategory_id'] ?? null,
            'requires_kyc'     => $request->boolean('requires_kyc'),
            'status'           => 0, // resubmit for approval
            'rejection_reason' => null,
        ]));

        return redirect()->route('user.jobs.listings.show', $id)
            ->with('success', 'Listing updated and resubmitted for review.');
    }

    public function destroy(int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->firstOrFail();

        if ($listing->cover_image) {
            removeFile(config('jobstation.upload_paths.work_cover'), $listing->cover_image);
        }

        $listing->delete();

        return redirect()->route('user.jobs.listings.index')
            ->with('success', 'Job listing deleted.');
    }

    public function boost(Request $request, int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->where('status', 1)
            ->firstOrFail();

        if ($listing->boostRequests()->where('status', 0)->exists()) {
            return back()->with('error', 'You already have a pending boost request for this listing. Please wait for admin approval.');
        }

        $maxDays    = max(1, (int) gs()->boost_days_job);
        $days       = max(1, min((int) $request->input('days', 1), $maxDays));
        $costPerDay = (float) gs()->boost_cost_job;
        $totalCost  = $costPerDay * $days;

        if (! CoinService::hasBalance($user, $totalCost)) {
            return back()->with('error', "Insufficient coins. You need " . formatCoins($totalCost) . " coins for {$days} day(s).");
        }

        DB::transaction(function () use ($user, $listing, $days, $totalCost) {
            CoinService::deduct($user, $totalCost, 'job-' . $listing->id, 'boost_request', "Boost request: {$listing->title} ({$days} days)");

            BoostRequest::create([
                'boostable_type' => JobListing::class,
                'boostable_id'   => $listing->id,
                'user_id'        => $user->id,
                'days'           => $days,
                'coins_paid'     => $totalCost,
                'status'         => 0,
            ]);
        });

        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Boost Request',
                "{$user->username} requested a boost for job listing \"{$listing->title}\" ({$days} day(s)).",
                'info', route('admin.boost-requests.index'));
        }

        return back()->with('success', 'Boost request submitted! Admin will review and activate it shortly. Coins have been held.');
    }

    public function close(int $id)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $id)
            ->where('employer_id', $user->id)
            ->where('status', 1)
            ->firstOrFail();

        $listing->update(['status' => 2]);

        return back()->with('success', 'Job listing closed.');
    }

    // ── Application Actions (employer side) ──────────────────────

    public function reviewApplication(int $listingId, int $appId)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $listingId)->where('employer_id', $user->id)->firstOrFail();
        $app     = $listing->applications()->with('applicant')->findOrFail($appId);

        if (! $app->is_read) {
            $app->update(['is_read' => true]);
        }

        return view('user.jobs.applications.review', compact('listing', 'app'));
    }

    public function updateApplicationStatus(Request $request, int $listingId, int $appId)
    {
        $user    = $this->employer();
        $listing = JobListing::where('id', $listingId)->where('employer_id', $user->id)->firstOrFail();
        $app     = $listing->applications()->findOrFail($appId);

        $data = $request->validate([
            'status'        => ['required', 'in:1,2,3,4'],
            'employer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $app->update([
            'status'        => $data['status'],
            'employer_note' => $data['employer_note'] ?? null,
            'reviewed_at'   => now(),
        ]);

        return back()->with('success', 'Application status updated.');
    }
}
