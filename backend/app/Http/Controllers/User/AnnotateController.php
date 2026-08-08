<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WorkSubmission;
use App\Services\ActivityLogger;
use App\Services\TaskDataValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The annotate-code flow: enter a code, work through the console, submit.
 *
 * WHY THE CODE IS CHECKED AGAINST THE LOGGED-IN USER
 *
 * The code is the only way in, per the platform design. It is not, however, the only
 * check: a code identifies a submission, and a submission belongs to a worker. Codes
 * get pasted into support chats and screenshots, and without an ownership check
 * anyone holding one could open and complete someone else's paid task.
 */
class AnnotateController extends Controller
{
    /** The code-entry screen. One field, nothing else. */
    public function enter()
    {
        return view('user.annotate.enter');
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'annotate_code' => ['required', 'string', 'max:24'],
        ]);

        $code = strtoupper(trim($data['annotate_code']));

        $submission = WorkSubmission::with('work')
            ->where('annotate_code', $code)
            ->first();

        if (! $submission) {
            return back()->withInput()->withErrors([
                'annotate_code' => 'That code was not recognised. Check it and try again.',
            ]);
        }

        if ((int) $submission->worker_id !== (int) Auth::guard('web')->id()) {
            // Deliberately the same message as "not found". Telling a stranger that a
            // code is real but belongs to somebody else is information they can use.
            return back()->withInput()->withErrors([
                'annotate_code' => 'That code was not recognised. Check it and try again.',
            ]);
        }

        return redirect()->route('user.annotate.console', $code);
    }

    /**
     * The console itself, with the task payload injected.
     */
    public function console(string $code)
    {
        $submission = $this->findForWorker($code);

        if (is_string($submission)) {
            return redirect()->route('user.annotate.enter')->with('error', $submission);
        }

        $work = $submission->work;

        if (empty($work?->task_json)) {
            // Predates the JSON format. Better a clear message than a console that
            // loads and renders nothing.
            return redirect()->route('user.annotate.enter')
                ->with('error', 'This task has no question file attached yet. Contact support quoting ' . ($work->task_id ?? $code) . '.');
        }

        // No separate "in progress" status is set. The delivery_status enum has
        // not_started, submitted, revision_requested, approved, rejected, expired —
        // adding a seventh value would mean auditing every scope, report and the
        // legacy `status` mirror that derives from it. Whether someone has started
        // is already answered by progress_saved_at being non-null, which is the
        // question anyone actually asks.

        $payload = app(TaskDataValidator::class)->forBrowser($work->task_json);

        // The task ID the worker sees, so a support conversation has a reference.
        $payload['meta']['task_id'] = $work->task_id;

        // One identity, not two. The console would otherwise generate its own short
        // annotator ID and display that instead of the code in the URL, leaving the
        // worker with two references and no way to know which one matters.
        //
        // Overwritten rather than merged: a task file supplying its own annotator
        // fields would be asking the worker to self-identify, which is meaningless
        // once they are logged in and can be typed as anybody.
        $payload['meta']['annotator_fields'] = [[
            'id'       => 'annotator_id',
            'label'    => 'Your task code',
            'required' => true,
            'value'    => $submission->annotate_code,
        ]];

        return view('user.annotate.console', [
            'submission' => $submission,
            'work'       => $work,
            'taskData'   => $payload,
            'progress'   => $submission->progress_payload,
            'deadline'   => $submission->deadline_at,
        ]);
    }

    /**
     * Autosave. Called repeatedly while the worker types, so it does as little as
     * possible and never validates the payload — half-finished work is expected to
     * be incomplete, and rejecting it would lose the very thing being saved.
     */
    public function save(Request $request, string $code)
    {
        $submission = $this->findForWorker($code);

        if (is_string($submission)) {
            return response()->json(['ok' => false, 'error' => $submission], 422);
        }

        $data = $request->validate([
            'progress' => ['required', 'array'],
        ]);

        $submission->update([
            'progress_payload'  => $data['progress'],
            'progress_saved_at' => now(),
        ]);

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    /**
     * Final submission. Starts the review clock; no credit yet.
     */
    public function submit(Request $request, string $code)
    {
        $submission = $this->findForWorker($code);

        if (is_string($submission)) {
            return response()->json(['ok' => false, 'error' => $submission], 422);
        }

        $data = $request->validate([
            'result' => ['required', 'array'],
        ]);

        $result = $data['result'];

        // Answered-count check against the task's own required questions. The console
        // enforces this too, but the console runs on the worker's machine.
        $required = collect($submission->work->task_json['questions'] ?? [])
            ->filter(fn ($q) => ($q['required'] ?? false) === true)
            ->pluck('id');

        $answered = collect($result['responses'] ?? [])
            ->filter(fn ($r) => ! in_array($r['answer'] ?? null, [null, '', []], true))
            ->pluck('question_id');

        $missing = $required->diff($answered);

        if ($missing->isNotEmpty()) {
            return response()->json([
                'ok'      => false,
                'error'   => 'Some required questions have no answer.',
                'missing' => $missing->values(),
            ], 422);
        }

        // Identity is stamped server-side, not taken from the client.
        //
        // The console auto-generates its own annotator ID, which is right for offline
        // use and meaningless here — a worker quoting it to support would quote
        // something nobody can look up, and it is trivially editable in any case.
        // Overwriting it means the stored payload always identifies the real worker
        // and the real submission, whatever the browser sent.
        $result['annotator'] = array_merge($result['annotator'] ?? [], [
            'annotate_code' => $submission->annotate_code,
            'worker_id'     => $submission->worker_id,
            'username'      => $submission->worker?->username,
            'task_id'       => $submission->work->task_id,
        ]);

        $hours = (int) ($submission->work->auto_approve_hours ?: (gs()->default_review_hours ?? 48));

        $submitted = DB::transaction(function () use ($submission, $result, $hours) {
            $locked = WorkSubmission::whereKey($submission->id)->lockForUpdate()->first();

            // A double-submit — two tabs, or a retried request — must not reset a
            // review clock that has already started.
            if ($locked->delivery_status === WorkSubmission::DEL_SUBMITTED) {
                return false;
            }

            $locked->update([
                'result_payload'  => $result,
                'delivery_status' => WorkSubmission::DEL_SUBMITTED,
                'submitted_at'    => now(),
                'review_deadline' => now()->addHours($hours),
                'is_read'         => 0,
            ]);

            return true;
        });

        if (! $submitted) {
            return response()->json([
                'ok'      => true,
                'already' => true,
                'message' => 'This task was already submitted.',
            ]);
        }

        ActivityLogger::log('task.submitted', $submission, [
            'worker_id' => $submission->worker_id,
            'task_id'   => $submission->work->task_id,
        ]);

        return response()->json([
            'ok'              => true,
            'submission_code' => $submission->annotate_code,
            'review_deadline' => $submission->fresh()->review_deadline?->toIso8601String(),
            'message'         => 'Submitted. It will be reviewed within ' . $hours . ' hours.',
        ]);
    }

    /**
     * @return WorkSubmission|string the submission, or a message explaining why not
     */
    private function findForWorker(string $code): WorkSubmission|string
    {
        $submission = WorkSubmission::with('work')
            ->where('annotate_code', strtoupper(trim($code)))
            ->first();

        if (! $submission || (int) $submission->worker_id !== (int) Auth::guard('web')->id()) {
            return 'That code was not recognised.';
        }

        if ($submission->application_status !== WorkSubmission::APP_APPROVED) {
            return 'This application has not been approved, so the task is not open yet.';
        }

        if ($submission->delivery_status === WorkSubmission::DEL_SUBMITTED) {
            // Blocked rather than reopened. Reopening would let someone change answers
            // after delivery, and the review may already have started.
            return 'You have already submitted this task. It is waiting to be reviewed — '
                 . 'nothing else is needed from you.';
        }

        if (in_array($submission->delivery_status, [
            WorkSubmission::DEL_APPROVED,
            WorkSubmission::DEL_REJECTED,
        ], true)) {
            return 'This task has already been reviewed and closed.';
        }

        if ($submission->delivery_status === WorkSubmission::DEL_EXPIRED) {
            return 'This task passed its deadline and was closed.';
        }

        if ($submission->deadline_at && $submission->deadline_at->isPast()) {
            return 'The deadline for this task has passed.';
        }

        return $submission;
    }
}
