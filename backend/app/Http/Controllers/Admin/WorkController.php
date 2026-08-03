<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
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

    public function pending(Request $request)
    {
        $works = Work::with(['category', 'poster'])
            ->where('approval_status', 0)
            ->latest()
            ->paginate(config('jobstation.per_page', 20));

        return view('admin.works.pending', compact('works'));
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
            'coins_per_worker'           => ['required', 'numeric', 'min:0.01'],
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
        $data['total_coins']     = $data['coins_per_worker'] * $data['worker_slots'];
        $data['slug']            = Str::slug($data['title']) . '-' . Str::random(6);

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
            'coins_per_worker'           => ['required', 'numeric', 'min:0.01'],
            'avg_minutes'                => ['nullable', 'integer', 'min:1'],
            'description'                => ['required', 'string'],
            'work_status'                => ['required', 'in:0,1,2'],
            'cover_image'                => ['nullable', 'image', 'max:2048'],
            'allow_multiple_submissions' => ['sometimes', 'boolean'],
            'requires_kyc'               => ['sometimes', 'boolean'],
        ]);

        $data['allow_multiple_submissions'] = $request->boolean('allow_multiple_submissions');
        $data['requires_kyc']               = $request->boolean('requires_kyc');
        $data['total_coins'] = $data['coins_per_worker'] * $data['worker_slots'];

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
}
