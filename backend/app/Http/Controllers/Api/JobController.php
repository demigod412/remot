<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\WorkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /** GET /api/jobs — public job listing browse */
    public function index(Request $request): JsonResponse
    {
        $query = JobListing::open()->with(['category', 'employer']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id'))      $query->where('category_id', $request->category_id);
        if ($request->filled('location_type'))     $query->where('location_type', $request->location_type);
        if ($request->filled('employment_type'))   $query->where('employment_type', $request->employment_type);
        if ($skillId = $request->input('skill_id')) {
            $query->whereHas('skills', fn ($q) => $q->where('skills.id', $skillId));
        }

        $listings = $query->orderByDesc('is_featured')->latest()->paginate(15);

        $appliedIds = [];
        // This route is public, so resolve the bearer token explicitly (optional auth) —
        // see the same fix in show()/WorkController::show for why plain user() won't work here.
        if ($user = $request->user('sanctum')) {
            $appliedIds = JobApplication::where('applicant_id', $user->id)->pluck('job_listing_id')->toArray();
        }

        return response()->json([
            'data' => $listings->map(fn ($l) => $this->listingResource($l, $appliedIds)),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page'    => $listings->lastPage(),
                'total'        => $listings->total(),
            ],
        ]);
    }

    /** GET /api/jobs/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $listing = JobListing::open()->with(['category', 'employer', 'skills'])->findOrFail($id);

        $application  = null;
        $isBookmarked = false;
        // This route is public, so resolve the bearer token explicitly (optional auth).
        if ($user = $request->user('sanctum')) {
            $application = JobApplication::where('job_listing_id', $id)
                ->where('applicant_id', $user->id)
                ->first();
            $isBookmarked = \App\Models\JobBookmark::where('user_id', $user->id)
                ->where('job_listing_id', $id)->exists();
        }

        $listingData = $this->listingResource($listing, [], detailed: true);
        $listingData['is_bookmarked'] = $isBookmarked;

        return response()->json([
            'listing'     => $listingData,
            'application' => $application ? [
                'id'         => $application->id,
                'status'     => $application->status,
                'created_at' => $application->created_at,
            ] : null,
        ]);
    }

    /** POST /api/jobs/{id}/apply — auth required */
    public function apply(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $listing = JobListing::open()->findOrFail($id);

        if ($listing->employer_id === $user->id) {
            return response()->json(['message' => 'You cannot apply to your own listing.'], 422);
        }

        if ($listing->requires_kyc && (int) $user->kyc_status !== 1) {
            return response()->json(['message' => 'This job requires identity (KYC) verification. Please complete KYC first.'], 403);
        }

        if (JobApplication::where('job_listing_id', $id)->where('applicant_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already applied.'], 422);
        }

        $data = $request->validate([
            'cover_letter'             => ['required', 'string', 'min:50', 'max:3000'],
            'expected_salary'          => ['nullable', 'numeric', 'min:0'],
            'expected_salary_currency' => ['nullable', 'string', 'max:10'],
            'portfolio_url'            => ['nullable', 'url', 'max:255'],
            'resume'                   => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
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

        return response()->json(['message' => 'Application submitted successfully.']);
    }

    /** DELETE /api/jobs/{id}/apply — withdraw a still-pending application. */
    public function cancelApplication(Request $request, int $id): JsonResponse
    {
        $application = JobApplication::where('job_listing_id', $id)
            ->where('applicant_id', $request->user()->id)
            ->first();

        if (! $application) {
            return response()->json(['message' => 'You have not applied to this job.'], 404);
        }

        // Only a pending application can be withdrawn; once the employer has
        // started reviewing it (status >= 1) it is locked.
        if ($application->status != 0) {
            return response()->json(['message' => 'This application is already under review and cannot be cancelled.'], 422);
        }

        $application->delete();
        JobListing::where('id', $id)->where('application_count', '>', 0)->decrement('application_count');

        return response()->json(['message' => 'Application withdrawn.']);
    }

    /** POST /api/v1/jobs — authenticated. Mirrors the web employer's "Post a Job" form. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

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
            'salary_visible'  => ['sometimes', 'boolean'],
            'requirements'    => ['nullable', 'string'],
            'benefits'        => ['nullable', 'string'],
            'closes_at'       => ['nullable', 'date', 'after:today'],
            'cover_image'     => ['nullable', 'image', 'max:2048'],
            'requires_kyc'    => ['sometimes', 'boolean'],
        ]);

        $coverImage = null;
        if ($request->hasFile('cover_image')) {
            $coverImage = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        $listing = JobListing::create([
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

        foreach (\App\Models\Admin::all() as $admin) {
            \App\Models\AdminNotification::notify(
                $admin->id,
                'New Job Listing Pending Review',
                "{$user->username} submitted a new job listing: \"{$data['title']}\"",
                'info',
                route('admin.jobs.listings.show', $listing->id)
            );
        }

        return response()->json([
            'message' => 'Job listing submitted for review.',
            'listing' => $this->listingResource($listing, []),
        ], 201);
    }

    /** GET /api/jobs/my-applications — auth required */
    public function myApplications(Request $request): JsonResponse
    {
        $query = JobApplication::where('applicant_id', $request->user()->id)
            ->with(['listing.category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $apps = $query->latest()->paginate(15);

        return response()->json([
            'data' => $apps->map(fn ($a) => [
                'id'           => $a->id,
                'status'       => $a->status,
                'cover_letter' => $a->cover_letter,
                'created_at'   => $a->created_at,
                'listing'      => $a->listing ? $this->listingResource($a->listing, []) : null,
            ]),
            'meta' => [
                'current_page' => $apps->currentPage(),
                'last_page'    => $apps->lastPage(),
                'total'        => $apps->total(),
            ],
        ]);
    }

    private function listingResource(JobListing $l, array $appliedIds, bool $detailed = false): array
    {
        $locTypes = [1 => 'Remote', 2 => 'On-site', 3 => 'Hybrid'];
        $empTypes = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'freelance' => 'Freelance'];

        $base = [
            'id'              => $l->id,
            'title'           => $l->title,
            'location'        => $l->location,
            'location_type'   => $l->location_type,
            'location_label'  => $locTypes[$l->location_type] ?? null,
            'employment_type' => $l->employment_type,
            'employment_label'=> $empTypes[$l->employment_type] ?? null,
            'salary_min'      => $l->salary_visible ? (float) $l->salary_min : null,
            'salary_max'      => $l->salary_visible ? (float) $l->salary_max : null,
            'salary_currency' => $l->salary_visible ? $l->salary_currency : null,
            'is_featured'     => $l->is_featured,
            'requires_kyc'    => (bool) $l->requires_kyc,
            'application_count' => $l->application_count,
            'closes_at'       => $l->closes_at,
            'created_at'      => $l->created_at,
            'already_applied' => in_array($l->id, $appliedIds),
            'category'        => $l->category ? ['id' => $l->category->id, 'name' => $l->category->name] : null,
            'employer'        => $l->employer ? ['id' => $l->employer->id, 'name' => $l->employer->fullname] : null,
            'cover_image'     => $l->cover_image
                ? fileUrl(config('jobstation.upload_paths.work_cover'), $l->cover_image)
                : null,
        ];

        if ($detailed) {
            $base['description']  = $l->description;
            $base['requirements'] = $l->requirements;
            $base['benefits']     = $l->benefits;
            $base['skills']       = $l->skills->pluck('name');
        }

        return $base;
    }
}
