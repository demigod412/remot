<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\Work;
use App\Models\WorkBookmark;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkBrowseController extends Controller
{
    public function __construct(protected TaskApplicationService $applications)
    {
    }

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

        $categories = WorkCategory::where('status', 1)
            ->withCount(['works' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();
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

        // One application per worker per task, enforced by a unique index on
        // work_submissions (work_id, worker_id). allow_multiple_submissions was
        // retired in migration 0070, so there is no re-apply branch any more:
        // any existing row means this worker is done applying to this task.
        $alreadyApplied = (bool) $userSubmission;
        $canReapply     = false;

        $similar = Work::active()
            ->where('category_id', $work->category_id)
            ->where('id', '!=', $work->id)
            ->limit(4)
            ->get();

        return view('user.works.detail', compact('work', 'slotsRemaining', 'alreadyApplied', 'canReapply', 'userSubmission', 'similar'));
    }

    /** Start a task from the dashboard → create the submission → go to the proof form. */
    /**
     * Apply to a task from the in-dashboard task detail page.
     *
     * This used to be a genuine "start work now" action: it called
     * WorkSubmission::create() directly, which meant it
     *
     *   - charged no application fee at all (no CoinService call, no ledger row),
     *   - left application_status / delivery_status at their column defaults
     *     instead of going through the lifecycle,
     *   - honoured works.allow_multiple_submissions, retired in migration 0070,
     *   - skipped the eligibility, user-type and reliability checks,
     *   - took the worker straight to the legacy proof form, so the task read as
     *     started before admin had approved anything or delivered a package.
     *
     * It is now a thin wrapper over TaskApplicationService::apply(), the same
     * tested path Web works.apply uses: row-locked slot check, fee charged once
     * against a stored fee_reference, application left at Awaiting Review.
     * The route name is kept so existing links and bookmarks do not break.
     */
    public function start(string $slug)
    {
        $user = Auth::guard('web')->user();
        $work = Work::with('category')->where('slug', $slug)->firstOrFail();

        try {
            $submission = $this->applications->apply($user, $work);
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $charged = (float) $submission->fee_paid > 0
            ? ' ' . formatCoins($submission->fee_paid) . ' was deducted.'
            : '';

        return redirect()->route('user.tasks.index')
            ->with('success', 'Application submitted and awaiting review.' . $charged);
    }
}
