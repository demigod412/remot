<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\Work;
use App\Models\WorkBookmark;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkBrowseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();

        $query = Work::active()
            ->where(function ($q) use ($user) {
                $q->where('poster_type', '!=', 2)
                  ->orWhere('poster_id', '!=', $user->id);
            })
            ->with(['category', 'subcategory']);

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
            'coins_high' => $query->orderByDesc('coins_per_worker'),
            'coins_low'  => $query->orderBy('coins_per_worker'),
            'slots'      => $query->orderByDesc('worker_slots'),
            default      => $query->orderByDesc('is_featured')->latest(),
        };

        $works = $query->paginate(15)->withQueryString();

        // Mark which works user has already applied to / bookmarked
        $appliedIds = WorkSubmission::where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->pluck('work_id')
            ->flip();

        $bookmarkedIds = WorkBookmark::where('user_id', $user->id)
            ->pluck('work_id')
            ->flip();

        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        $skills     = Skill::active()->orderBy('name')->get();

        return view('user.works.browse', compact('works', 'appliedIds', 'bookmarkedIds', 'categories', 'skills'));
    }

    /** In-dashboard work detail (worker view). */
    public function show(string $slug)
    {
        $user = Auth::guard('web')->user();
        $work = Work::active()->with(['category', 'subcategory'])->where('slug', $slug)->firstOrFail();

        $slotsRemaining = $work->slots_remaining;
        $userSubmission = WorkSubmission::where('work_id', $work->id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->latest()
            ->first();

        $alreadyApplied = false;
        $canReapply     = false;
        if ($userSubmission) {
            if ($work->allow_multiple_submissions && $userSubmission->status === 2) {
                $canReapply = true;
            } else {
                $alreadyApplied = true;
            }
        }

        $similar = Work::active()
            ->where('category_id', $work->category_id)
            ->where('id', '!=', $work->id)
            ->limit(4)
            ->get();

        return view('user.works.detail', compact('work', 'slotsRemaining', 'alreadyApplied', 'canReapply', 'userSubmission', 'similar'));
    }

    /** Start a task from the dashboard → create the submission → go to the proof form. */
    public function start(string $slug)
    {
        $work = Work::active()->where('slug', $slug)->firstOrFail();
        $user = Auth::guard('web')->user();

        if ($work->slots_remaining <= 0) {
            return back()->with('error', 'No slots remaining for this task.');
        }

        $existing = WorkSubmission::where('work_id', $work->id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->latest()
            ->first();

        if ($existing && (! $work->allow_multiple_submissions || $existing->status !== 2)) {
            // Already started — take them straight to the proof form.
            return redirect()->route('user.submissions.proof', $existing->id);
        }

        if ($work->requires_kyc && $user->kyc_status !== 1) {
            return back()->with('error', 'This task requires KYC verification. Please complete identity verification first.');
        }

        if ($work->poster_type === 2 && $work->poster_id === $user->id) {
            return back()->with('error', 'You cannot start your own task.');
        }

        $submission = WorkSubmission::create([
            'work_id'          => $work->id,
            'work_poster_id'   => $work->poster_id,
            'work_poster_type' => $work->poster_type,
            'worker_id'        => $user->id,
            'worker_type'      => 2,
            'status'           => 0,
            'deadline_at'      => $work->auto_approve_hours ? now()->addHours($work->auto_approve_hours) : null,
        ]);

        return redirect()->route('user.submissions.proof', $submission->id)
            ->with('success', 'Task started — submit your proof below.');
    }
}
