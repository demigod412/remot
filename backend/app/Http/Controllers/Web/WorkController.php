<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $query = Work::active()->with(['category', 'subcategory']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($catId = $request->input('category')) {
            $query->where('category_id', $catId);
        }

        if ($subId = $request->input('subcategory')) {
            $query->where('subcategory_id', $subId);
        }

        if ($skillId = $request->input('skill')) {
            $query->whereHas('skills', fn($q) => $q->where('skills.id', $skillId));
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'coins_high'  => $query->orderByDesc('coins_per_worker'),
            'coins_low'   => $query->orderBy('coins_per_worker'),
            'slots'       => $query->orderByDesc('worker_slots'),
            default       => $query->orderByRaw('(is_featured = 1 AND (featured_until IS NULL OR featured_until > NOW())) DESC')->latest(),
        };

        $works      = $query->paginate(12)->withQueryString();
        // withCount so the category tiles can show a live task count. Constrained
        // to Work::active() for the same reason the listing is: a category whose
        // only tasks are held or unapproved should read as empty, not populated.
        $categories = WorkCategory::where('status', 1)
            ->withCount(['works' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();
        $skills     = Skill::active()->orderBy('name')->get();
        $jobs       = JobListing::where('status', 1)->with(['employer', 'category'])->latest()->paginate(12);
        $totalWorks = Work::active()->count();
        $totalJobs  = JobListing::where('status', 1)->count();

        return view('web.works.index', compact('works', 'categories', 'skills', 'jobs', 'totalWorks', 'totalJobs'));
    }

    public function show(string $slug)
    {
        $work = Work::active()
            ->with(['category', 'subcategory'])
            ->where('slug', $slug)
            ->firstOrFail();

        $slotsRemaining  = $work->slots_remaining;
        $alreadyApplied  = false;
        $canReapply      = false;
        $userSubmission  = null;

        if ($user = Auth::guard('web')->user()) {
            $userSubmission = WorkSubmission::where('work_id', $work->id)
                ->where('worker_id', $user->id)
                ->where('worker_type', 2)
                ->latest()
                ->first();

            if ($userSubmission) {
                if ($work->allow_multiple_submissions && $userSubmission->status === 2) {
                    // Previous submission approved — user can do it again
                    $canReapply     = true;
                    $alreadyApplied = false;
                } else {
                    $alreadyApplied = true;
                }
            }
        }

        $similar = Work::active()
            ->where('category_id', $work->category_id)
            ->where('id', '!=', $work->id)
            ->limit(4)
            ->get();

        return view('web.works.show', compact('work', 'slotsRemaining', 'alreadyApplied', 'canReapply', 'userSubmission', 'similar'));
    }

    public function apply(Request $request, string $slug)
    {
        $work = Work::active()->where('slug', $slug)->firstOrFail();
        $user = Auth::guard('web')->user();

        if ($work->slots_remaining <= 0) {
            return back()->with('error', 'No slots remaining for this work.');
        }

        $existingSubmission = WorkSubmission::where('work_id', $work->id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->latest()
            ->first();

        if ($existingSubmission) {
            if (!$work->allow_multiple_submissions) {
                return back()->with('error', 'You have already applied for this work.');
            }
            if ($existingSubmission->status !== 2) {
                return back()->with('error', 'You can re-apply only after your current submission is approved.');
            }
        }

        if ($work->requires_kyc && $user->kyc_status !== 1) {
            return back()->with('error', 'This job requires KYC verification. Please complete identity verification before applying.');
        }

        // Prevent poster from applying to own work
        if ($work->poster_type === 2 && $work->poster_id === $user->id) {
            return back()->with('error', 'You cannot apply to your own work.');
        }

        WorkSubmission::create([
            'work_id'        => $work->id,
            'work_poster_id' => $work->poster_id,
            'work_poster_type' => $work->poster_type,
            'worker_id'      => $user->id,
            'worker_type'    => 2,
            'status'         => 0,
            'deadline_at'    => $work->auto_approve_hours
                ? now()->addHours($work->auto_approve_hours)
                : null,
        ]);

        return redirect()->route('user.submissions.index')
            ->with('success', 'Successfully applied! Go to your submissions to submit proof.');
    }
}
