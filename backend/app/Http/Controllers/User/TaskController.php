<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskResultRequest;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskApplicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function __construct(protected TaskApplicationService $applications)
    {
    }

    /**
     * Apply to a task. All the money and slot logic is in the service, inside one
     * transaction. This method only translates the outcome into a flash message.
     */
    public function apply(string $slug)
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

    /**
     * The worker's own applications and assigned tasks.
     */
    public function index()
    {
        $user = Auth::guard('web')->user();

        $submissions = WorkSubmission::with('work')
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->latest()
            ->paginate(config('jobstation.per_page', 20));

        return view('user.tasks.index', compact('submissions'));
    }

    /**
     * The task package and result upload form, only once admin has approved.
     */
    public function show(int $id)
    {
        $submission = $this->ownedSubmission($id);

        if (! $submission->isApprovedToWork()) {
            return redirect()->route('user.tasks.index')
                ->with('error', 'This application has not been approved yet.');
        }

        return view('user.tasks.show', compact('submission'));
    }

    /**
     * Download one file from the delivered task package. Served through the app so
     * the private disk is never exposed, and only to the assigned worker.
     */
    public function downloadTaskFile(int $id, int $index)
    {
        $submission = $this->ownedSubmission($id);

        abort_unless($submission->isApprovedToWork(), 403);

        $files    = $submission->task_files ?? [];
        $filename = $files[$index] ?? null;

        abort_if($filename === null, 404);

        $path = trim(config('jobstation.upload_paths.task_files', 'uploads/tasks/packages'), '/')
              . '/' . $filename;

        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
    }

    public function submitResult(TaskResultRequest $request, int $id)
    {
        $submission = $this->ownedSubmission($id);

        if (! $submission->isOpenForWorker()) {
            return back()->with('error', 'This task is not open for submission.');
        }

        if ($submission->deadline_at && $submission->deadline_at->isPast()) {
            return back()->with('error', 'The deadline for this task has passed.');
        }

        $path  = config('jobstation.upload_paths.task_results', 'uploads/tasks/results');
        $saved = uploadPrivateFile($request->file('result_file'), $path);

        DB::transaction(function () use ($submission, $request, $saved) {
            $files   = $submission->proof_files ?? [];
            $files[] = $saved;

            $submission->update([
                'proof_files'     => $files,
                'proof_note'      => $request->input('proof_note'),
                'delivery_status' => WorkSubmission::DEL_SUBMITTED,
                'submitted_at'    => now(),
                'is_read'         => 0,
                // Admin's review clock. If nobody acts before this, ProcessWorkTimers
                // auto-approves and the worker gets paid.
                'deadline_at'     => now()->addHours(
                    $submission->work?->review_window_hours
                        ?? (int) config('jobstation.task_review_hours', 48)
                ),
            ]);
        });

        return redirect()->route('user.tasks.index')
            ->with('success', 'Result submitted. It will be reviewed shortly.');
    }

    protected function ownedSubmission(int $id): WorkSubmission
    {
        $user = Auth::guard('web')->user();

        return WorkSubmission::with('work.category')
            ->where('id', $id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->firstOrFail();
    }
}
