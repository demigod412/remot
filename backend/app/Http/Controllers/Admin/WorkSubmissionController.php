<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkSubmissionController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = WorkSubmission::with(['work', 'worker']);

        if ($request->filled('work_id')) {
            $query->where('work_id', $request->input('work_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('worker', fn ($q) =>
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )->orWhereHas('work', fn ($q) =>
                $q->where('title', 'like', "%{$search}%")
            );
        }

        // Mark all loaded submissions as read
        $query->latest();
        $submissions = $query->paginate(config('jobstation.per_page', 20))->withQueryString();

        WorkSubmission::whereIn('id', $submissions->pluck('id'))->update(['is_read' => 1]);

        $stats = [
            'total'    => WorkSubmission::count(),
            'pending'  => WorkSubmission::where('status', 1)->count(),
            'approved' => WorkSubmission::where('status', 2)->count(),
            'rejected' => WorkSubmission::where('status', 3)->count(),
        ];

        // Work filter hint
        $filterWork = $request->filled('work_id')
            ? Work::find($request->input('work_id'))
            : null;

        return view('admin.submissions.index', compact('submissions', 'stats', 'filterWork'));
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id)
    {
        $submission = WorkSubmission::with(['work', 'worker', 'workPoster'])->findOrFail($id);
        $submission->update(['is_read' => 1]);

        return view('admin.submissions.show', compact('submission'));
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id)
    {
        $submission = WorkSubmission::with('work')->findOrFail($id);

        if ($submission->status === 2) {
            return back()->with('error', 'Submission is already approved.');
        }

        DB::transaction(function () use ($submission) {
            $submission->status       = 2;
            $submission->submitted_at = $submission->submitted_at ?? now();
            $submission->save();

            // Credit worker
            $worker = User::find($submission->worker_id);
            if ($worker && $submission->work) {
                $reward = (float) $submission->work->coins_per_worker;
                $worker->increment('coin_balance', $reward);
                $worker->refresh();

                LedgerEntry::create([
                    'user_id'       => $worker->id,
                    'coins'         => $reward,
                    'fee'           => 0,
                    'balance_after' => $worker->coin_balance,
                    'entry_type'    => '+',
                    'reference'     => generateReference(),
                    'description'   => 'Earned: "' . $submission->work->title . '"',
                    'category'      => 'work_earn',
                ]);
            }
        });

        $submission->load('worker', 'work');
        if ($submission->worker) {
            NotifyService::send($submission->worker, 'SUBMISSION_APPROVED', [
                'work_title' => $submission->work->title ?? '',
                'coins'      => number_format($submission->work->coins_per_worker ?? 0, 0),
            ]);
        }

        return back()->with('success', 'Submission approved and coins credited.');
    }

    // -------------------------------------------------------------------------
    // Reject
    // -------------------------------------------------------------------------

    public function reject(Request $request, int $id)
    {
        $submission = WorkSubmission::with(['worker', 'work'])->findOrFail($id);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($submission->status === 2) {
            return back()->with('error', 'Cannot reject an already approved submission.');
        }

        $submission->status           = 3;
        $submission->rejection_reason = $data['rejection_reason'];
        $submission->save();

        if ($submission->worker) {
            NotifyService::send($submission->worker, 'SUBMISSION_REJECTED', [
                'work_title' => $submission->work->title ?? '',
                'reason'     => $data['rejection_reason'],
            ]);
        }

        return back()->with('success', 'Submission rejected.');
    }

    // -------------------------------------------------------------------------
    // Bulk Approve
    // -------------------------------------------------------------------------

    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:work_submissions,id'],
        ]);

        $approved = 0;

        DB::transaction(function () use ($data, &$approved) {
            $submissions = WorkSubmission::with('work')
                ->whereIn('id', $data['ids'])
                ->where('status', '!=', 2)
                ->get();

            foreach ($submissions as $submission) {
                $submission->status = 2;
                $submission->save();

                $worker = User::find($submission->worker_id);
                if ($worker && $submission->work) {
                    $reward = (float) $submission->work->coins_per_worker;
                    $worker->increment('coin_balance', $reward);
                    $worker->refresh();

                    LedgerEntry::create([
                        'user_id'       => $worker->id,
                        'coins'         => $reward,
                        'fee'           => 0,
                        'balance_after' => $worker->coin_balance,
                        'entry_type'    => '+',
                        'reference'     => generateReference(),
                        'description'   => 'Earned: "' . $submission->work->title . '"',
                        'category'      => 'work_earn',
                    ]);
                }
                $approved++;

                if ($worker && $submission->work) {
                    NotifyService::send($worker, 'SUBMISSION_APPROVED', [
                        'work_title' => $submission->work->title,
                        'coins'      => number_format($submission->work->coins_per_worker, 0),
                    ]);
                }
            }
        });

        return back()->with('success', "{$approved} submission(s) approved and coins credited.");
    }
}
