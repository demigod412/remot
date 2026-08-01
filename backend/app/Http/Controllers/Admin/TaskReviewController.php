<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskDeliveryRequest;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskReviewService;
use Illuminate\Http\Request;

/**
 * Admin review of task applications and deliveries.
 *
 * Thin by design: every state change and every coin movement lives in
 * TaskReviewService so it can be tested without an HTTP layer.
 */
class TaskReviewController extends Controller
{
    public function __construct(protected TaskReviewService $reviews)
    {
    }

    // -------------------------------------------------------------------------
    // Queues
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = WorkSubmission::with(['work', 'worker']);

        // Default to the queue that actually needs admin attention.
        $tab = $request->input('tab', 'applications');

        match ($tab) {
            'deliveries' => $query->awaitingDeliveryReview(),
            'revisions'  => $query->revisionRequested(),
            'settled'    => $query->whereIn('delivery_status', [
                WorkSubmission::DEL_APPROVED,
                WorkSubmission::DEL_REJECTED,
                WorkSubmission::DEL_EXPIRED,
            ]),
            default      => $query->awaitingApplicationReview(),
        };

        if ($request->filled('work_id')) {
            $query->where('work_id', (int) $request->input('work_id'));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('worker', fn ($q) => $q
                ->where('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $submissions = $query->latest()
            ->paginate(config('jobstation.per_page', 20))
            ->withQueryString();

        $stats = [
            'applications' => WorkSubmission::awaitingApplicationReview()->count(),
            'deliveries'   => WorkSubmission::awaitingDeliveryReview()->count(),
            'revisions'    => WorkSubmission::revisionRequested()->count(),
        ];

        $filterWork = $request->filled('work_id')
            ? Work::find($request->input('work_id'))
            : null;

        return view('admin.submissions.review', compact('submissions', 'stats', 'tab', 'filterWork'));
    }

    public function show(int $id)
    {
        $submission = WorkSubmission::with(['work.category', 'worker'])->findOrFail($id);
        $submission->update(['is_read' => 1]);

        return view('admin.submissions.review-show', compact('submission'));
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function approveApplication(TaskDeliveryRequest $request, int $id)
    {
        $submission = WorkSubmission::with('work')->findOrFail($id);

        $stored = [];
        $path   = config('jobstation.upload_paths.task_files', 'uploads/tasks/packages');

        foreach ($request->file('task_files') as $file) {
            $stored[] = uploadPrivateFile($file, $path);
        }

        try {
            $this->reviews->approveApplication(
                $submission,
                $stored,
                $request->input('task_instructions')
            );
        } catch (ApplicationException $e) {
            // Clean up the uploads we just made, otherwise a rejected action leaves
            // orphan files on disk.
            foreach ($stored as $filename) {
                removePrivateFile($path, $filename);
            }

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Application approved and the task package delivered to the worker.');
    }

    public function rejectApplication(Request $request, int $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $submission = WorkSubmission::with('work')->findOrFail($id);

        try {
            $this->reviews->rejectApplication($submission, $data['rejection_reason']);
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $refunded = (float) $submission->fee_paid > 0
            ? ' The application fee of ' . formatCoins($submission->fee_paid) . ' was refunded.'
            : '';

        return back()->with('success', 'Application rejected.' . $refunded);
    }

    public function requestRevision(Request $request, int $id)
    {
        $data = $request->validate([
            'revision_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $submission = WorkSubmission::with('work')->findOrFail($id);

        try {
            $this->reviews->requestRevision($submission, $data['revision_notes']);
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Revision requested. The worker has a fresh deadline.');
    }

    public function approveSubmission(int $id)
    {
        $submission = WorkSubmission::with('work.category')->findOrFail($id);

        try {
            $submission = $this->reviews->approveSubmission($submission);
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Work approved. Worker paid ' . formatCoins($submission->work->net_payout ?? 0) . '.'
        );
    }

    public function rejectSubmission(Request $request, int $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $submission = WorkSubmission::with('work')->findOrFail($id);

        try {
            $this->reviews->rejectSubmission($submission, $data['rejection_reason']);
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Work rejected. No payout was made and the fee was not refunded.');
    }
}
