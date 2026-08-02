<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSubmission;
use App\Services\TaskReviewService;
use Illuminate\Http\Request;

class TaskReviewController extends Controller
{
    public function __construct(private TaskReviewService $review)
    {
    }

    public function index(Request $request)
    {
        $submissions = WorkSubmission::query()
            ->with([
                'work:id,title,amount,category_id',
                'work.category:id,name,commission_percent',
                'user:id,name,email',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('delivery_status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = (string) $request->string('q');
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"))
                        ->orWhereHas('work', fn ($w) => $w->where('title', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = WorkSubmission::query()
            ->selectRaw('delivery_status, COUNT(*) as total')
            ->groupBy('delivery_status')
            ->pluck('total', 'delivery_status');

        return view('admin.task-review.index', compact('submissions', 'statusCounts'));
    }

    public function show(WorkSubmission $submission)
    {
        $submission->load(['work.category', 'user']);

        $preview = app(\App\Services\CoinService::class)->splitCommission(
            (float) $submission->work->amount,
            (float) ($submission->work->category->commission_percent ?? 0)
        );

        return view('admin.task-review.show', compact('submission', 'preview'));
    }

    public function deliver(WorkSubmission $submission)
    {
        $this->review->markDelivered($submission);

        return back()->with('success', 'Submission marked as delivered.');
    }

    public function approve(Request $request, WorkSubmission $submission)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission = $this->review->approveSubmission($submission, $data['admin_note'] ?? null);

        return redirect()
            ->route('admin.task-review.show', $submission)
            ->with('success', sprintf(
                'Approved. $%s USD credited to %s.',
                number_format((float) $submission->net_amount, 2),
                $submission->user->name
            ));
    }

    public function reject(Request $request, WorkSubmission $submission)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->review->rejectSubmission($submission, $data['reason']);

        return back()->with('success', 'Submission rejected. No application fee refunded.');
    }

    public function revision(Request $request, WorkSubmission $submission)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $this->review->requestRevision($submission, $data['note']);

        return back()->with('success', 'Revision requested.');
    }
}