<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Rating;
use App\Models\Work;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ratable_type' => 'required|in:work,contract',
            'ratable_id'   => 'required|integer|min:1',
            'ratee_id'     => 'required|integer|exists:users,id',
            'rating'       => 'required|integer|min:1|max:5',
            'review'       => 'nullable|string|max:500',
        ]);

        $user        = Auth::user();
        $rateeId     = (int) $request->ratee_id;
        $ratableId   = (int) $request->ratable_id;
        $ratableType = $request->ratable_type === 'work' ? Work::class : Contract::class;

        if ($user->id === $rateeId) {
            return back()->with('error', 'You cannot rate yourself.');
        }

        if ($request->ratable_type === 'work') {
            $work = Work::where('id', $ratableId)->where('poster_type', 2)->firstOrFail();
            $isPoster = $work->poster_id === $user->id;
            $isWorker = WorkSubmission::where('work_id', $ratableId)
                ->where('worker_id', $user->id)
                ->where('status', 2)
                ->exists();

            abort_if(!$isPoster && !$isWorker, 403);

            if ($isPoster) {
                // Poster rates an approved worker
                abort_unless(
                    WorkSubmission::where('work_id', $ratableId)
                        ->where('worker_id', $rateeId)
                        ->where('status', 2)
                        ->exists(),
                    403
                );
            } else {
                // Worker rates the poster
                abort_unless($rateeId === $work->poster_id, 403);
            }
        } else {
            $contract = Contract::findOrFail($ratableId);
            abort_if($contract->status !== 3, 403, 'Contract must be completed before rating.');
            $isEmployer = $contract->employer_id === $user->id;
            $isWorker   = $contract->worker_id === $user->id;
            abort_if(!$isEmployer && !$isWorker, 403);
            if ($isEmployer) abort_unless($rateeId === $contract->worker_id, 403);
            if ($isWorker)   abort_unless($rateeId === $contract->employer_id, 403);
        }

        $alreadyRated = Rating::where('rater_id', $user->id)
            ->where('ratable_id', $ratableId)
            ->where('ratable_type', $ratableType)
            ->exists();

        if ($alreadyRated) {
            return back()->with('info', 'You have already submitted a rating for this.');
        }

        Rating::create([
            'rater_id'     => $user->id,
            'ratee_id'     => $rateeId,
            'ratable_id'   => $ratableId,
            'ratable_type' => $ratableType,
            'rating'       => $request->rating,
            'review'       => $request->review,
        ]);

        return back()->with('success', 'Rating submitted. It will be visible once the other party also rates.');
    }
}
