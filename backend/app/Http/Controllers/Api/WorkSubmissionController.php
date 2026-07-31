<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\WorkSubmission;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkSubmissionController extends Controller
{
    /** GET /api/v1/submissions — my submitted works (as worker) */
    public function mySubmissions(Request $request): JsonResponse
    {
        $query = $request->user()
            ->workSubmissions()
            ->with(['work.category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->latest()->paginate(15);

        return response()->json([
            'data' => $submissions->map(fn ($s) => $this->submissionResource($s)),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page'    => $submissions->lastPage(),
                'total'        => $submissions->total(),
            ],
        ]);
    }

    /** POST /api/v1/submissions/{id}/proof */
    public function submitProof(Request $request, int $id): JsonResponse
    {
        $user       = $request->user();
        $submission = WorkSubmission::where('id', $id)
            ->where('worker_id', $user->id)
            ->where('status', 0) // applied — awaiting the worker's proof
            ->firstOrFail();

        $request->validate([
            'proof_note'    => ['nullable', 'string', 'max:5000'],
            'proof_files'   => ['nullable', 'array', 'max:5'],
            'proof_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        // A proof needs at least a note (text/url) or an uploaded file.
        if (blank($request->proof_note) && ! $request->hasFile('proof_files')) {
            return response()->json([
                'message' => 'Please provide a proof note or upload a file.',
            ], 422);
        }

        $files = [];
        if ($request->hasFile('proof_files')) {
            foreach ($request->file('proof_files') as $file) {
                $files[] = uploadPrivateFile($file, 'work-proofs');
            }
        }

        $submission->update([
            'proof_note'  => $request->proof_note,
            'proof_files' => $files ?: null,
            'status'      => 1, // pending review by the poster
            'submitted_at'=> now(),
        ]);

        return response()->json(['message' => 'Proof submitted successfully.']);
    }

    /** GET /api/v1/submissions/my-work-submissions — as poster reviewing workers */
    public function myWorkSubmissions(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = WorkSubmission::with(['work', 'worker'])
            ->whereHas('work', fn ($q) => $q->where('poster_id', $user->id)->where('poster_type', 0));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($workId = $request->input('work_id')) {
            $query->where('work_id', $workId);
        }

        $submissions = $query->latest()->paginate(15);

        return response()->json([
            'data' => $submissions->map(fn ($s) => $this->submissionResource($s, withProof: true)),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page'    => $submissions->lastPage(),
                'total'        => $submissions->total(),
            ],
        ]);
    }

    /** POST /api/v1/submissions/{id}/approve — poster approves */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user       = $request->user();
        $submission = WorkSubmission::with(['work', 'worker'])
            ->whereHas('work', fn ($q) => $q->where('poster_id', $user->id)->where('poster_type', 0))
            ->where('id', $id)
            ->where('status', 2)
            ->firstOrFail();

        DB::transaction(function () use ($submission) {
            $worker = $submission->worker;
            $coins  = (float) $submission->work->coins_per_worker;

            $worker->increment('coin_balance', $coins);
            $worker->refresh();

            LedgerEntry::create([
                'user_id'       => $worker->id,
                'coins'         => $coins,
                'fee'           => 0,
                'balance_after' => $worker->coin_balance,
                'entry_type'    => '+',
                'category'      => 'work_earn',
                'reference'     => $submission->work->slug,
                'description'   => 'Earned from: ' . $submission->work->title,
            ]);

            $submission->update(['status' => 3]); // approved

            NotifyService::send($worker, 'SUBMISSION_APPROVED', [
                'work_title' => $submission->work->title,
                'coins'      => number_format($coins, 0),
            ]);
        });

        return response()->json(['message' => 'Submission approved and coins credited.']);
    }

    /** POST /api/v1/submissions/{id}/reject — poster rejects */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $submission = WorkSubmission::with(['work', 'worker'])
            ->whereHas('work', fn ($q) => $q->where('poster_id', $user->id)->where('poster_type', 0))
            ->where('id', $id)
            ->where('status', 2)
            ->firstOrFail();

        $submission->update([
            'status'          => 4,
            'rejection_reason'=> $request->reason,
        ]);

        if ($submission->worker) {
            NotifyService::send($submission->worker, 'SUBMISSION_REJECTED', [
                'work_title' => $submission->work->title,
                'reason'     => $request->reason ?? 'No reason provided.',
            ]);
        }

        return response()->json(['message' => 'Submission rejected.']);
    }

    private function submissionResource(WorkSubmission $s, bool $withProof = false): array
    {
        $data = [
            'id'         => $s->id,
            'status'     => $s->status,
            'work'       => $s->work ? [
                'id'               => $s->work->id,
                'title'            => $s->work->title,
                'slug'             => $s->work->slug,
                'proof_type'       => $s->work->proof_type,
                'coins_per_worker' => (float) $s->work->coins_per_worker,
                'cover_image'      => $s->work->cover_image
                    ? fileUrl(config('jobstation.upload_paths.work_cover'), $s->work->cover_image)
                    : null,
            ] : null,
            'created_at' => $s->created_at,
        ];

        if ($withProof) {
            $data['worker']     = $s->worker ? [
                'id'       => $s->worker->id,
                'username' => $s->worker->username,
                'avatar'   => $s->worker->image
                    ? fileUrl(config('jobstation.upload_paths.user_avatar'), $s->worker->image)
                    : null,
            ] : null;
            $data['proof_note'] = $s->proof_note;
            $data['proof_files']= collect($s->proof_files ?? [])->map(
                fn ($f, $i) => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'secure.workProof', now()->addMinutes(30), ['submission' => $s->id, 'index' => $i]
                )
            )->values()->all();
            $data['submitted_at'] = $s->submitted_at;
            $data['rejection_reason'] = $s->rejection_reason;
        }

        return $data;
    }
}
