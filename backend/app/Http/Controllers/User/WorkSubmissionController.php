<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkSubmissionController extends Controller
{
    // ───────────────── MY SUBMISSIONS (worker view) ─────────────────

    public function mySubmissions(Request $request)
    {
        $user  = Auth::guard('web')->user();
        $query = $user->workSubmissions()->with('work');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.submissions.index', compact('submissions'));
    }

    /**
     * Show proof submission form for a work the user applied to.
     */
    public function showProofForm(int $id)
    {
        $user       = Auth::guard('web')->user();
        $submission = WorkSubmission::where('id', $id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->with('work')
            ->firstOrFail();

        if ($submission->status !== 0) {
            return redirect()->route('user.submissions.index')
                ->with('error', 'Proof can only be submitted for applied submissions.');
        }

        return view('user.submissions.proof', compact('submission'));
    }

    /**
     * Submit proof for a work.
     */
    public function submitProof(Request $request, int $id)
    {
        $user       = Auth::guard('web')->user();
        $submission = WorkSubmission::where('id', $id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->firstOrFail();

        if ($submission->status !== 0) {
            return back()->with('error', 'This submission cannot be updated.');
        }

        $request->validate([
            'proof_note'  => ['required', 'string', 'min:20'],
            'proof_files' => ['nullable', 'array', 'max:5'],
            'proof_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $proofFiles = $submission->proof_files ?? [];
        if ($request->hasFile('proof_files')) {
            foreach ($request->file('proof_files') as $file) {
                $proofFiles[] = uploadPrivateFile($file, 'work-proofs');
            }
        }

        $submission->update([
            'proof_note'   => $request->proof_note,
            'proof_files'  => $proofFiles,
            'status'       => 1, // under review
            'submitted_at' => now(),
        ]);

        return redirect()->route('user.submissions.index')
            ->with('success', 'Proof submitted successfully. Awaiting review.');
    }

    // ───────────────── MY WORK SUBMISSIONS (poster view) ─────────────────

    /**
     * View submissions on a work I posted.
     */
    public function myWorkSubmissions(Request $request, int $id)
    {
        $user = Auth::guard('web')->user();
        $work = Work::where('id', $id)
            ->where('poster_id', $user->id)
            ->where('poster_type', 2)
            ->firstOrFail();

        $query = $work->submissions()->with('worker');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.works.submissions', compact('work', 'submissions'));
    }

    /**
     * Review a specific proof submitted on my work.
     */
    public function reviewProof(int $workId, int $submissionId)
    {
        $user       = Auth::guard('web')->user();
        $work       = Work::where('id', $workId)->where('poster_id', $user->id)->where('poster_type', 2)->firstOrFail();
        $submission = WorkSubmission::where('id', $submissionId)->where('work_id', $work->id)->with('worker')->firstOrFail();

        return view('user.works.review-proof', compact('work', 'submission'));
    }

    /**
     * Approve a proof — credit worker coins.
     */
    public function approveProof(int $workId, int $submissionId)
    {
        $user       = Auth::guard('web')->user();
        $work       = Work::where('id', $workId)->where('poster_id', $user->id)->where('poster_type', 2)->firstOrFail();
        $submission = WorkSubmission::where('id', $submissionId)
            ->where('work_id', $work->id)
            ->where('status', 1) // must be under review
            ->firstOrFail();

        DB::transaction(function () use ($work, $submission) {
            $submission->update(['status' => 2, 'is_read' => 1]);

            // Credit worker
            $worker = $submission->worker;
            if ($worker && $worker instanceof \App\Models\User) {
                $worker->increment('coin_balance', $work->coins_per_worker);
                $worker->refresh();
                LedgerEntry::create([
                    'user_id'       => $worker->id,
                    'coins'         => $work->coins_per_worker,
                    'fee'           => 0,
                    'balance_after' => $worker->coin_balance,
                    'entry_type'    => '+',
                    'category'      => 'work_earn',
                    'reference'     => $work->slug,
                    'description'   => 'Approved: ' . $work->title,
                ]);
            }

            // Finish work if all slots filled
            $approvedCount = $work->approvedSubmissions()->count();
            if ($approvedCount >= $work->worker_slots) {
                $work->update(['work_status' => 2]);
            }
        });

        $submission->load('worker');
        if ($submission->worker instanceof \App\Models\User) {
            NotifyService::send($submission->worker, 'SUBMISSION_APPROVED', [
                'work_title' => $work->title,
                'coins'      => number_format($work->coins_per_worker, 0),
            ]);
            \App\Models\UserNotification::notify(
                $submission->worker->id,
                'submission_approved',
                'Submission Approved',
                'Your submission for "' . $work->title . '" was approved. ' . number_format($work->coins_per_worker, 0) . ' ' . coinSymbol() . ' credited.',
                route('user.submissions.index'),
                'check-circle'
            );
        }

        return back()->with('success', 'Proof approved and worker paid.');
    }

    /**
     * Reject a proof.
     */
    public function rejectProof(Request $request, int $workId, int $submissionId)
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $user       = Auth::guard('web')->user();
        $work       = Work::where('id', $workId)->where('poster_id', $user->id)->where('poster_type', 2)->firstOrFail();
        $submission = WorkSubmission::where('id', $submissionId)
            ->where('work_id', $work->id)
            ->where('status', 1)
            ->firstOrFail();

        $submission->update([
            'status'           => 3,
            'rejection_reason' => $request->rejection_reason,
            'is_read'          => 1,
        ]);

        $submission->load('worker');
        if ($submission->worker instanceof \App\Models\User) {
            NotifyService::send($submission->worker, 'SUBMISSION_REJECTED', [
                'work_title' => $work->title,
                'reason'     => $request->rejection_reason,
            ]);
            \App\Models\UserNotification::notify(
                $submission->worker->id,
                'submission_rejected',
                'Submission Rejected',
                'Your submission for "' . $work->title . '" was rejected.',
                route('user.submissions.index'),
                'x-circle'
            );
        }

        return back()->with('success', 'Proof rejected.');
    }

    /**
     * Bulk approve proofs on my work.
     */
    public function bulkApprove(Request $request, int $id)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        $user = Auth::guard('web')->user();
        $work = Work::where('id', $id)->where('poster_id', $user->id)->where('poster_type', 2)->firstOrFail();

        $submissions = WorkSubmission::whereIn('id', $request->ids)
            ->where('work_id', $work->id)
            ->where('status', 1)
            ->get();

        DB::transaction(function () use ($work, $submissions) {
            foreach ($submissions as $sub) {
                $sub->update(['status' => 2, 'is_read' => 1]);

                $worker = $sub->worker;
                if ($worker instanceof \App\Models\User) {
                    $worker->increment('coin_balance', $work->coins_per_worker);
                    $worker->refresh();
                    LedgerEntry::create([
                        'user_id'       => $worker->id,
                        'coins'         => $work->coins_per_worker,
                        'fee'           => 0,
                        'balance_after' => $worker->coin_balance,
                        'entry_type'    => '+',
                        'category'      => 'work_earn',
                        'reference'     => $work->slug,
                        'description'   => 'Approved: ' . $work->title,
                    ]);
                    NotifyService::send($worker, 'SUBMISSION_APPROVED', [
                        'work_title' => $work->title,
                        'coins'      => number_format($work->coins_per_worker, 0),
                    ]);
                }
            }

            $approvedCount = $work->approvedSubmissions()->count();
            if ($approvedCount >= $work->worker_slots) {
                $work->update(['work_status' => 2]);
            }
        });

        return back()->with('success', count($submissions) . ' proofs approved.');
    }
}
