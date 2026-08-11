<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkController extends Controller
{
    // -------------------------------------------------------------------------
    // All Works
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = Work::with(['category', 'poster']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('approval')) {
            $query->where('approval_status', $request->input('approval'));
        }

        if ($request->filled('status')) {
            $query->where('work_status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->filled('poster')) {
            $query->where('poster_type', $request->input('poster'));
        }

        // Slot occupancy and expiry, so a full or closed task can be found and
        // reposted instead of sitting invisible at the bottom of the list.
        match ($request->input('slots')) {
            'filled'    => $query->slotsFilled(),
            'available' => $query->slotsAvailable(),
            'expired'   => $query->expiredTasks(),
            default     => null,
        };

        $works      = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();
        $categories = WorkCategory::orderBy('name')->get();

        $stats = [
            'total'    => Work::count(),
            'pending'  => Work::where('approval_status', 0)->count(),
            'active'   => Work::where('approval_status', 1)->where('work_status', 1)->count(),
            'rejected' => Work::where('approval_status', 2)->count(),
        ];

        return view('admin.works.index', compact('works', 'categories', 'stats'));
    }

    // -------------------------------------------------------------------------
    // Pending Approval
    // -------------------------------------------------------------------------

    /**
     * Kept as a redirect rather than deleted.
     *
     * This listed works with approval_status = 0 — works awaiting admin approval.
     * Admin-posted tasks are auto-approved and user-posted gigs are disabled, so
     * nothing could ever appear here; it was a permanently empty page in the menu.
     *
     * The route name survives because it is linked from the sidebar and will be
     * bookmarked. Enable user gigs and this is worth restoring.
     */
    public function pending(Request $request)
    {
        return redirect()->route('admin.task-review.index', ['tab' => 'deliveries']);
    }

    // -------------------------------------------------------------------------
    // Create / Store (Admin posts a work directly)
    // -------------------------------------------------------------------------

    public function create()
    {
        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        return view('admin.works.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                      => ['required', 'string', 'max:200'],
            'category_id'                => ['required', 'exists:work_categories,id'],
            'subcategory_id'             => ['nullable', 'exists:work_subcategories,id'],
            'worker_slots'               => ['required', 'integer', 'min:1'],
            // DISPLAY ONLY. Cosmetic head start for the shown applicant count on a
            // new task. Never enters slot arithmetic. See migration 0072.
            'display_application_boost'  => ['nullable', 'integer', 'min:0', 'max:100000'],
            // USD paid to each worker on approval. Independent of coins_per_worker.
            'payout_usd'                 => ['required', 'numeric', 'min:0', 'max:1000000'],
            // The task payload. Required when creating, because a task with no
            // questions is not something a worker can be charged to apply for.
            'task_file'                  => ['nullable', 'file', 'mimes:json,txt', 'max:2048'],
            // Vestigial for admin-posted tasks. The worker's reward is payout_usd and the
            // application fee comes from the category, so there is nothing for a coin
            // figure to do here. The column stays for the legacy user-gig flow.
            'coins_per_worker'           => ['nullable', 'numeric', 'min:0'],
            'avg_minutes'                => ['nullable', 'integer', 'min:1'],
            'description'                => ['required', 'string'],
            'work_status'                => ['required', 'in:0,1'],
            'cover_image'                => ['nullable', 'image', 'max:2048'],
            'allow_multiple_submissions' => ['sometimes', 'boolean'],
            'requires_kyc'               => ['sometimes', 'boolean'],
        ]);

        $data['allow_multiple_submissions'] = $request->boolean('allow_multiple_submissions');
        $data['requires_kyc']               = $request->boolean('requires_kyc');
        $data['poster_id']       = Auth::guard('admin')->id();
        $data['poster_type']     = 1;
        $data['approval_status'] = 1; // admin posts auto-approved
        $data['coins_per_worker'] = $data['coins_per_worker'] ?? 0;
        $data['total_coins']      = $data['coins_per_worker'] * $data['worker_slots'];
        $data['slug']            = Str::slug($data['title']) . '-' . Str::random(6);

        // The public reference, generated here and never parsed from the uploaded
        // file — a file cannot claim an ID that belongs to another task.
        $data['task_id'] = app(\App\Services\TaskIdGenerator::class)->generate();

        $taskErrors = $this->attachTaskFile($request, $data);

        if ($taskErrors !== []) {
            return back()->withInput()->withErrors(['task_file' => implode(' ', $taskErrors)]);
        }

        if (empty($data['task_json'])) {
            return back()->withInput()->withErrors([
                'task_file' => 'A task JSON file is required. Workers pay a non-refundable fee to apply, so a task with no questions must not be publishable.',
            ]);
        }

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        $work = Work::create($data);

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'Work Created',
            "Work \"{$work->title}\" created by admin.",
            'info',
            route('admin.works.show', $work->id)
        );

        return redirect()->route('admin.works.show', $work->id)
            ->with('success', 'Work created successfully.');
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id)
    {
        $work = Work::with(['category', 'subcategory', 'poster'])->findOrFail($id);

        $submissionStats = [
            'total'    => WorkSubmission::where('work_id', $id)->count(),
            'pending'  => WorkSubmission::where('work_id', $id)->where('status', 1)->count(),
            'approved' => WorkSubmission::where('work_id', $id)->where('status', 2)->count(),
            'rejected' => WorkSubmission::where('work_id', $id)->where('status', 3)->count(),
        ];

        $recentSubmissions = WorkSubmission::with('worker')
            ->where('work_id', $id)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.works.show', compact('work', 'submissionStats', 'recentSubmissions'));
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id)
    {
        $work        = Work::findOrFail($id);
        $categories  = WorkCategory::where('status', 1)->orderBy('name')->get();
        $subcategories = $work->category_id
            ? WorkSubcategory::where('category_id', $work->category_id)->where('status', 1)->get()
            : collect();

        return view('admin.works.edit', compact('work', 'categories', 'subcategories'));
    }

    public function update(Request $request, int $id)
    {
        $work = Work::findOrFail($id);

        $data = $request->validate([
            'title'                      => ['required', 'string', 'max:200'],
            'category_id'                => ['required', 'exists:work_categories,id'],
            'subcategory_id'             => ['nullable', 'exists:work_subcategories,id'],
            'worker_slots'               => ['required', 'integer', 'min:1'],
            // DISPLAY ONLY. Cosmetic head start for the shown applicant count on a
            // new task. Never enters slot arithmetic. See migration 0072.
            'display_application_boost'  => ['nullable', 'integer', 'min:0', 'max:100000'],
            // USD paid to each worker on approval. Independent of coins_per_worker.
            'payout_usd'                 => ['required', 'numeric', 'min:0', 'max:1000000'],
            // The task payload. Required when creating, because a task with no
            // questions is not something a worker can be charged to apply for.
            'task_file'                  => ['nullable', 'file', 'mimes:json,txt', 'max:2048'],
            // Vestigial for admin-posted tasks. The worker's reward is payout_usd and the
            // application fee comes from the category, so there is nothing for a coin
            // figure to do here. The column stays for the legacy user-gig flow.
            'coins_per_worker'           => ['nullable', 'numeric', 'min:0'],
            'avg_minutes'                => ['nullable', 'integer', 'min:1'],
            'description'                => ['required', 'string'],
            'work_status'                => ['required', 'in:0,1,2'],
            'cover_image'                => ['nullable', 'image', 'max:2048'],
            'allow_multiple_submissions' => ['sometimes', 'boolean'],
            'requires_kyc'               => ['sometimes', 'boolean'],
        ]);

        $data['allow_multiple_submissions'] = $request->boolean('allow_multiple_submissions');
        $data['requires_kyc']               = $request->boolean('requires_kyc');
        $data['coins_per_worker'] = $data['coins_per_worker'] ?? 0;
        $data['total_coins']      = $data['coins_per_worker'] * $data['worker_slots'];

        // Optional on edit. attachTaskFile only writes task_json when a file was
        // actually sent, so saving the form without one leaves the payload intact
        // rather than emptying it.
        $taskErrors = $this->attachTaskFile($request, $data);

        if ($taskErrors !== []) {
            return back()->withInput()->withErrors(['task_file' => implode(' ', $taskErrors)]);
        }

        // A task already open for applications must not lose its questions.
        $existing = Work::find($id);
        if ($existing && empty($existing->task_json) && empty($data['task_json'])) {
            return back()->withInput()->withErrors([
                'task_file' => 'This task has no question file yet. Upload one before saving, or workers cannot open it.',
            ]);
        }

        if ($request->hasFile('cover_image')) {
            if ($work->cover_image) {
                removeFile(config('jobstation.upload_paths.work_cover'), $work->cover_image);
            }
            $data['cover_image'] = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        $work->update($data);

        return redirect()->route('admin.works.show', $work->id)
            ->with('success', 'Work updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id)
    {
        $work = Work::findOrFail($id);

        // Refund poster if they paid coins
        if ($work->poster_type === 2 && $work->approval_status === 1) {
            $this->refundPoster($work);
        }

        if ($work->cover_image) {
            removeFile(config('jobstation.upload_paths.work_cover'), $work->cover_image);
        }

        $work->delete();

        return redirect()->route('admin.works.index')
            ->with('success', 'Work deleted.');
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id)
    {
        $work = Work::findOrFail($id);

        if ($work->approval_status === 1) {
            return back()->with('error', 'Work is already approved.');
        }

        $work->approval_status = 1;
        $work->work_status     = 1; // activate on approval
        $work->rejection_reason = null;
        $work->save();

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'Work Approved',
            "Work \"{$work->title}\" has been approved.",
            'success',
            route('admin.works.show', $work->id)
        );

        return back()->with('success', 'Work approved and set to active.');
    }

    // -------------------------------------------------------------------------
    // Reject
    // -------------------------------------------------------------------------

    public function reject(Request $request, int $id)
    {
        $work = Work::findOrFail($id);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($work, $data) {
            $work->approval_status  = 2;
            $work->work_status      = 0;
            $work->rejection_reason = $data['rejection_reason'];
            $work->save();

            // Refund coins to poster if they paid
            if ($work->poster_type === 2) {
                $this->refundPoster($work);
            }
        });

        return back()->with('success', 'Work rejected and poster notified.');
    }

    // -------------------------------------------------------------------------
    // Feature Toggle
    // -------------------------------------------------------------------------

    public function toggleFeature(Request $request, int $id)
    {
        $work = Work::findOrFail($id);

        if ($work->is_featured) {
            $work->update(['is_featured' => false, 'featured_until' => null]);
            return back()->with('success', 'Work removed from featured.');
        }

        $days = (int) gs()->boost_days_work;
        $work->update(['is_featured' => true, 'featured_until' => now()->addDays($days)]);
        return back()->with('success', "Work featured for {$days} days.");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function refundPoster(Work $work): void
    {
        if ($work->poster_type !== 2 || ! $work->poster_id) return;

        $poster = \App\Models\User::find($work->poster_id);
        if (! $poster || $work->total_coins <= 0) return;

        $poster->increment('coin_balance', $work->total_coins);
        $poster->refresh();

        LedgerEntry::create([
            'user_id'       => $poster->id,
            'coins'         => $work->total_coins,
            'fee'           => 0,
            'balance_after' => $poster->coin_balance,
            'entry_type'    => '+',
            'reference'     => generateReference(),
            'description'   => 'Refund: work "' . $work->title . '" rejected/deleted',
            'category'      => 'work_refund',
        ]);
    }

    /**
     * Add capacity to an existing task.
     *
     * The task keeps its identity, its applications and its history — only the slot
     * count grows. Workers who already applied still cannot apply again, because one
     * application per worker per task is a locked rule and this does not touch it.
     *
     * Use this when a task simply needs more hands. Use repost() when you want the
     * same work done again by a fresh set of workers, including previous ones.
     */
    public function extendSlots(Request $request, int $id)
    {
        $work = Work::findOrFail($id);

        $data = $request->validate([
            'additional_slots' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $before = (int) $work->worker_slots;
        $work->update([
            'worker_slots' => $before + (int) $data['additional_slots'],
            // A task that finished because it filled up should open again.
            'work_status'  => $work->work_status === 2 ? 1 : $work->work_status,
            // total_coins tracks the legacy coin budget; keep it consistent.
            'total_coins'  => (float) $work->coins_per_worker * ($before + (int) $data['additional_slots']),
        ]);

        ActivityLogger::log('work.extend_slots', $work, [
            'slots_before' => $before,
            'slots_after'  => $work->worker_slots,
            'added'        => (int) $data['additional_slots'],
        ]);

        return back()->with('success', sprintf(
            'Added %d slot(s). "%s" now has %d slots, %d still open.',
            $data['additional_slots'],
            $work->title,
            $work->worker_slots,
            $work->slots_remaining
        ));
    }

    /**
     * Clone a task into a fresh one.
     *
     * A copy rather than a reset, deliberately. Resetting in place would mean either
     * deleting the completed applications — destroying the record of work already paid
     * for — or leaving them attached, in which case every previous worker is permanently
     * barred from the reposted task by the one-application-per-task rule.
     *
     * Cloning keeps the finished task and its history intact for reporting, and gives
     * the new one a clean slate that everybody, including previous workers, can apply to.
     *
     * The old task is marked finished so it stops appearing as open.
     */
    public function repost(Request $request, int $id)
    {
        $original = Work::findOrFail($id);

        $data = $request->validate([
            'worker_slots' => ['required', 'integer', 'min:1', 'max:10000'],
            'expires_at'   => ['nullable', 'date', 'after:now'],
            'close_original' => ['sometimes', 'boolean'],
        ]);

        $clone = null;

        DB::transaction(function () use ($original, $data, $request, &$clone) {
            $clone = Work::create([
                'poster_id'                 => Auth::guard('admin')->id(),
                'poster_type'               => 1,
                'category_id'               => $original->category_id,
                'subcategory_id'            => $original->subcategory_id,
                'title'                     => $original->title,
                'description'               => $original->description,
                'cover_image'               => $original->cover_image,
                'worker_slots'              => (int) $data['worker_slots'],
                'coins_per_worker'          => $original->coins_per_worker,
                'total_coins'               => (float) $original->coins_per_worker * (int) $data['worker_slots'],
                'payout_usd'                => $original->payout_usd,
                'avg_minutes'               => $original->avg_minutes,
                'requires_kyc'              => $original->requires_kyc,
                'auto_approve_hours'        => $original->auto_approve_hours,
                // Not carried over: a cosmetic applicant head start belongs to the run it
                // was set for, and copying it would silently inflate the new task too.
                'display_application_boost' => 0,
                'expires_at'                => $data['expires_at'] ?? null,
                'work_status'               => 1,
                'approval_status'           => 1,
                'slug'                      => Str::slug($original->title) . '-' . Str::random(6),
            ]);

            if ($request->boolean('close_original', true)) {
                $original->update(['work_status' => 2]);
            }

            ActivityLogger::log('work.repost', $clone, [
                'cloned_from'  => $original->id,
                'worker_slots' => $clone->worker_slots,
                'original_closed' => $request->boolean('close_original', true),
            ]);
        });

        return redirect()
            ->route('admin.works.edit', $clone->id)
            ->with('success', sprintf(
                'Reposted as a new task with %d slots. The original is kept for its history.',
                $clone->worker_slots
            ));
    }


    /**
     * Read, validate and attach an uploaded task payload.
     *
     * Returns the error list rather than throwing, so the caller can put the
     * problems back on the form beside the other validation messages instead of
     * losing everything the admin typed.
     *
     * @return array<int,string> empty when the file was accepted, or no file was sent
     */
    protected function attachTaskFile(\Illuminate\Http\Request $request, array &$data): array
    {
        if (! $request->hasFile('task_file')) {
            return [];
        }

        $json = file_get_contents($request->file('task_file')->getRealPath());

        $result = app(\App\Services\TaskDataValidator::class)->validate($json);

        if (! $result['ok']) {
            return $result['errors'];
        }

        $data['task_json']      = $result['data'];
        $data['question_count'] = $result['questions'];

        return [];
    }

}
